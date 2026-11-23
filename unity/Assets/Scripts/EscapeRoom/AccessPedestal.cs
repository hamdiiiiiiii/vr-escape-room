using System;
using System.Collections;
using UnityEngine;

// One pedestal in the lab. It connects its keypad to the code list (AccessCodeManager) and, on a correct code,
// to the vault door and the scene change. Set 'roomIndex' to choose which entry of the list it uses (0, 1, 2).
public class AccessPedestal : MonoBehaviour
{
    public int roomIndex;
    public KeypadController keypad;
    public VaultDoorController vault;

    public static event Action<AccessPedestal> ActiveChanged;      // PlayerInteraction listens to lock / unlock movement
    public static bool SequenceRunning { get; private set; }
    public static event Action<int, bool> Attempted;               // (roomIndex, wasCorrect): used by LabSessionBridge to report to the website

    public bool IsOpen { get; private set; }
    AccessCodeManager manager;
    bool waiting;

    void Start()
    {
        manager = AccessCodeManager.Instance != null ? AccessCodeManager.Instance : FindFirstObjectByType<AccessCodeManager>();
        if (vault == null) vault = FindFirstObjectByType<VaultDoorController>();
        if (keypad == null) { Debug.LogError("AccessPedestal: no keypad assigned on " + name); return; }
        keypad.Submitted += OnSubmitted;
        keypad.CloseRequested += Close;
        keypad.OpenRequested += Open;
        AccessRoom room = manager != null ? manager.Get(roomIndex) : null;
        keypad.SetRoomName(room != null ? room.roomName : "NO ROOM");
    }

    void OnDestroy()
    {
        SequenceRunning = false;
        if (keypad != null) { keypad.Submitted -= OnSubmitted; keypad.CloseRequested -= Close; keypad.OpenRequested -= Open; }
    }

    public void Open()
    {
        if (IsOpen || SequenceRunning) return;
        IsOpen = true;
        keypad.SetOpen(true);
        if (ActiveChanged != null) ActiveChanged(this);
    }

    public void Close()
    {
        if (!IsOpen || SequenceRunning) return;
        IsOpen = false;
        keypad.SetOpen(false);
        if (ActiveChanged != null) ActiveChanged(null);
    }

    void OnSubmitted(string code)
    {
        if (SequenceRunning || waiting) return;
        AccessRoom thisRoom = manager != null ? manager.Get(roomIndex) : null;
        if (thisRoom != null && thisRoom.useWebsiteCode) { StartCoroutine(WebLogin(thisRoom, code)); return; }
        bool correct = manager != null && manager.Check(roomIndex, code);
        if (Attempted != null) Attempted(roomIndex, correct);
        if (correct)
        {
            manager.Grant(roomIndex);
            SequenceRunning = true;
            keypad.ShowGranted();
            AccessRoom room = manager.Get(roomIndex);
            if (vault != null) vault.Open(() => GoToRoom(room.destinationScene));
            else GoToRoom(room.destinationScene);
        }
        else keypad.ShowDenied();
    }

    // this pedestal takes the 6-digit code generated on the website: the server checks it and starts the session
    IEnumerator WebLogin(AccessRoom room, string code)
    {
        waiting = true;
        keypad.ShowMessage("CHECKING...", new Color(0.3f, 0.85f, 1f), 20f);
        bool ok = false; string error = null;
        yield return ApiClient.VerifyCode(code, (response, err) => { ok = response != null; error = err; });
        waiting = false;
        if (ok)
        {
            SequenceRunning = true;
            keypad.ShowGranted();
            if (vault != null) vault.Open(() => GoToRoom(room.destinationScene));
            else GoToRoom(room.destinationScene);
        }
        else
        {
            string e = error == null ? "" : error.ToLowerInvariant();
            string msg = e.Contains("wrong") ? "WRONG CODE" : e.Contains("expired") ? "CODE EXPIRED" : e.Contains("reach") ? "SERVER OFFLINE" : "LOGIN FAILED";
            keypad.ShowMessage(msg, new Color(1f, 0.25f, 0.2f), 2.5f);
            keypad.Unlock();
        }
    }

    void GoToRoom(string scene)
    {
        if (SceneTransitionManager.Instance == null) { Debug.LogError("No SceneTransitionManager in the scene."); Failed("NO TRANSITION"); return; }
        SceneTransitionManager.Instance.Load(scene, () => Failed("ROOM NOT FOUND"));
    }

    // the destination scene is missing from Build Settings: stay in the lab and let the player try again
    void Failed(string message)
    {
        SequenceRunning = false;
        keypad.ShowMessage(message, new Color(1f, 0.7f, 0.2f), 2.5f);
        keypad.Unlock();
        if (vault != null) vault.Close();
    }
}
