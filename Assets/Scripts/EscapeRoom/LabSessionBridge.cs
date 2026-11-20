using System;
using System.Collections;
using System.Collections.Generic;
using System.Text;
using UnityEngine;
using UnityEngine.Networking;

// Connects the lab to the website. It does nothing unless the player logged in with a 6-digit code in the lobby
// (SessionData.HasSession). It lives for the whole game and creates itself, so you do not add it to any scene.
//   correct code on a pedestal  -> POST /api/sessions/{id}/results   (one result per puzzle)
//   every needed puzzle solved   -> POST /api/sessions/{id}/end  "escaped"
//   the room time limit runs out -> POST /api/sessions/{id}/end  "timed_out"
// Everything is sent in order, retried on network errors, and never blocks the game.
public class LabSessionBridge : MonoBehaviour
{
    // ---- JSON shapes (JsonUtility ignores fields we do not list) ----
    [Serializable] class ServerPuzzle { public string id; public string conceptTag; }
    [Serializable] class ServerRoom { public string[] puzzleOrder; public ServerPuzzle[] puzzles; }
    [Serializable] class ServerAnswer { public ServerRoom room; }
    [Serializable] class ResultBody { public string puzzleId; public string conceptTag; public bool solved; public int attempts; public int timeSeconds; public int hintsUsed; }
    [Serializable] class EndBody { public string reason; }
    class Job { public string path; public string body; public int tries; }

    static LabSessionBridge instance;

    readonly Queue<Job> queue = new Queue<Job>();
    readonly Dictionary<int, int> wrongTries = new Dictionary<int, int>();
    readonly HashSet<int> solved = new HashSet<int>();
    readonly List<string> puzzleIds = new List<string>();
    readonly List<string> puzzleTags = new List<string>();
    int sessionId;
    float sessionStart, lastResultTime;
    bool ended, worker, warnedOffline;

    [RuntimeInitializeOnLoadMethod(RuntimeInitializeLoadType.AfterSceneLoad)]
    static void Boot()
    {
        if (instance != null) return;
        GameObject go = new GameObject("LabSessionBridge");
        DontDestroyOnLoad(go);
        instance = go.AddComponent<LabSessionBridge>();
    }

    void OnEnable() { AccessPedestal.Attempted += OnAttempt; }
    void OnDisable() { AccessPedestal.Attempted -= OnAttempt; }

    void Update()
    {
        if (!SessionData.HasSession) return;
        if (SessionData.SessionId != sessionId) StartTracking();         // a new login: start fresh
        if (ended) return;
        int limit = SessionData.TimeLimitSeconds;
        if (limit > 0 && Time.realtimeSinceStartup - sessionStart >= limit) End("timed_out");
    }

    void StartTracking()
    {
        sessionId = SessionData.SessionId;
        sessionStart = lastResultTime = Time.realtimeSinceStartup;
        ended = false; warnedOffline = false;
        wrongTries.Clear(); solved.Clear(); puzzleIds.Clear(); puzzleTags.Clear(); queue.Clear();
        try
        {
            ServerAnswer a = JsonUtility.FromJson<ServerAnswer>(SessionData.RoomJson);
            if (a != null && a.room != null && a.room.puzzleOrder != null)
                foreach (string id in a.room.puzzleOrder)
                {
                    string tag = "General Testing";
                    if (a.room.puzzles != null) foreach (ServerPuzzle p in a.room.puzzles) if (p.id == id && !string.IsNullOrEmpty(p.conceptTag)) tag = p.conceptTag;
                    puzzleIds.Add(id); puzzleTags.Add(tag);
                }
        }
        catch (Exception e) { Debug.LogWarning("LabSessionBridge: could not read the room config: " + e.Message); }
        Debug.Log("LabSessionBridge: tracking session " + sessionId + " (" + puzzleIds.Count + " puzzles, limit " + SessionData.TimeLimitSeconds + " s).");
    }

    void OnAttempt(int roomIndex, bool correct)
    {
        if (!SessionData.HasSession)
        {
            if (!warnedOffline) { warnedOffline = true; Debug.Log("LabSessionBridge: no website session (you did not log in with a code), so nothing is sent. Offline test mode."); }
            return;
        }
        if (SessionData.SessionId != sessionId) StartTracking();
        if (ended) return;

        if (!correct) { wrongTries[roomIndex] = (wrongTries.ContainsKey(roomIndex) ? wrongTries[roomIndex] : 0) + 1; return; }
        if (solved.Contains(roomIndex)) return;
        solved.Add(roomIndex);

        // puzzle pedestals in order -> puzzles of the room in order (the website-code pedestal is the login, not a puzzle;
        // a pedestal without a puzzle is not scored)
        int pi = PuzzleIndex(roomIndex);
        if (pi >= 0 && pi < puzzleIds.Count)
        {
            int wrong = wrongTries.ContainsKey(roomIndex) ? wrongTries[roomIndex] : 0;
            float now = Time.realtimeSinceStartup;
            ResultBody r = new ResultBody
            {
                puzzleId = puzzleIds[pi], conceptTag = puzzleTags[pi], solved = true,
                attempts = Mathf.Clamp(wrong + 1, 1, 3), timeSeconds = Mathf.Max(0, Mathf.RoundToInt(now - lastResultTime)), hintsUsed = 0
            };
            lastResultTime = now;
            Enqueue("/api/sessions/" + sessionId + "/results", JsonUtility.ToJson(r));
        }

        int pedestals = 0;
        if (AccessCodeManager.Instance != null) foreach (AccessRoom r in AccessCodeManager.Instance.rooms) if (!r.useWebsiteCode) pedestals++;
        int needed = puzzleIds.Count > 0 ? Mathf.Min(puzzleIds.Count, Mathf.Max(1, pedestals)) : Mathf.Max(1, pedestals);
        int done = 0;
        foreach (int i in solved) { int k = PuzzleIndex(i); if (k >= 0 && (puzzleIds.Count == 0 || k < puzzleIds.Count)) done++; }
        if (done >= needed) End("escaped");
    }

    // position of a pedestal among the puzzle pedestals (-1 = it is the website login pedestal)
    static int PuzzleIndex(int roomIndex)
    {
        AccessCodeManager m = AccessCodeManager.Instance;
        if (m == null) return roomIndex;
        AccessRoom me = m.Get(roomIndex);
        if (me != null && me.useWebsiteCode) return -1;
        int n = 0;
        for (int i = 0; i < roomIndex && i < m.rooms.Count; i++) if (!m.rooms[i].useWebsiteCode) n++;
        return n;
    }

    void End(string reason)
    {
        if (ended) return;
        ended = true;
        Enqueue("/api/sessions/" + sessionId + "/end", JsonUtility.ToJson(new EndBody { reason = reason }));
        Debug.Log("LabSessionBridge: session ended (" + reason + ").");
    }

    void Enqueue(string path, string body)
    {
        queue.Enqueue(new Job { path = path, body = body });
        if (!worker) StartCoroutine(Work());
    }

    IEnumerator Work()
    {
        worker = true;
        string token = SessionData.Token;                                  // keep it: the end call may clear the session later
        while (queue.Count > 0)
        {
            Job j = queue.Peek();
            string url = SessionData.BaseUrl.TrimEnd('/') + j.path;
            using (UnityWebRequest req = new UnityWebRequest(url, "POST"))
            {
                req.uploadHandler = new UploadHandlerRaw(Encoding.UTF8.GetBytes(j.body));
                req.downloadHandler = new DownloadHandlerBuffer();
                req.SetRequestHeader("Content-Type", "application/json");
                req.SetRequestHeader("Accept", "application/json");
                req.SetRequestHeader("Authorization", "Bearer " + token);
                req.timeout = 10;
                yield return req.SendWebRequest();
                long status = req.responseCode;
                if (status >= 200 && status < 300) { queue.Dequeue(); Debug.Log("LabSessionBridge: sent " + j.path + " (" + status + ")."); }
                else if (status == 0 || status >= 500)
                {
                    if (++j.tries >= 5) { Debug.LogWarning("LabSessionBridge: giving up on " + j.path + " (server not reachable)."); queue.Dequeue(); }
                    else yield return new WaitForSeconds(2f);
                }
                else { Debug.LogWarning("LabSessionBridge: server refused " + j.path + " (" + status + "): " + req.downloadHandler.text); queue.Dequeue(); }
            }
        }
        worker = false;
    }
}
