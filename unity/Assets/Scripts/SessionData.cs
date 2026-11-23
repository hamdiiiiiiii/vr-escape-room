// Keeps the login result (token, session id, room info) while the game runs,
// so the room scenes can use it later to send results to the server.
public static class SessionData
{
    public static string BaseUrl = "http://localhost:8000";

    public static int SessionId;
    public static string Token;
    public static int RoomId;
    public static string RoomName;
    public static int TimeLimitSeconds;
    public static string RoomJson;   // the full room config as sent by the server

    public static bool HasSession { get { return !string.IsNullOrEmpty(Token); } }

    public static void Clear()
    {
        SessionId = 0;
        Token = null;
        RoomId = 0;
        RoomName = null;
        TimeLimitSeconds = 0;
        RoomJson = null;
    }
}
