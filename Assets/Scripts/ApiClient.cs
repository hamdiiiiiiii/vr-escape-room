using System;
using System.Collections;
using System.Text;
using UnityEngine;
using UnityEngine.Networking;

[Serializable]
public class VerifyRoom
{
    public int roomId;
    public string name;
    public int timeLimitSeconds;
}

[Serializable]
public class VerifyResponse
{
    public int sessionId;
    public string token;
    public VerifyRoom room;
}

[Serializable]
class VerifyRequest
{
    public string code;
}

// Talks to the Laravel server. Uses the same endpoint as the Postman "Verify code" request.
public static class ApiClient
{
    // Checks the 6-digit code. Calls onDone(response, null) on success,
    // or onDone(null, "message for the player") on failure.
    // NOTE: a good code starts the session on the server and works only once.
    public static IEnumerator VerifyCode(string code, Action<VerifyResponse, string> onDone)
    {
        string url = SessionData.BaseUrl.TrimEnd('/') + "/api/sessions/verify-code";
        string body = JsonUtility.ToJson(new VerifyRequest { code = code });

        using (var request = new UnityWebRequest(url, "POST"))
        {
            request.uploadHandler = new UploadHandlerRaw(Encoding.UTF8.GetBytes(body));
            request.downloadHandler = new DownloadHandlerBuffer();
            request.SetRequestHeader("Content-Type", "application/json");
            request.SetRequestHeader("Accept", "application/json");
            request.timeout = 10;

            yield return request.SendWebRequest();

            if (request.result == UnityWebRequest.Result.ConnectionError)
            {
                onDone(null, "Cannot reach the server. Is it running?");
                yield break;
            }

            if (request.result == UnityWebRequest.Result.ProtocolError)
            {
                long status = request.responseCode;
                if (status == 404) onDone(null, "Wrong code. Check it and try again.");
                else if (status == 410) onDone(null, "That code has expired. Get a new one.");
                else if (status == 422) onDone(null, "The code must be 6 digits.");
                else onDone(null, "Server error (" + status + ").");
                yield break;
            }

            string json = request.downloadHandler.text;
            VerifyResponse response = JsonUtility.FromJson<VerifyResponse>(json);

            if (response == null || string.IsNullOrEmpty(response.token) || response.room == null)
            {
                onDone(null, "The server sent an unexpected answer.");
                yield break;
            }

            SessionData.SessionId = response.sessionId;
            SessionData.Token = response.token;
            SessionData.RoomId = response.room.roomId;
            SessionData.RoomName = response.room.name;
            SessionData.TimeLimitSeconds = response.room.timeLimitSeconds;
            SessionData.RoomJson = json;

            onDone(response, null);
        }
    }
}
