using System;
using System.Collections.Generic;
using UnityEngine;

public enum AccessStatus { Locked, Granted }

// One room entry. All four values are editable in the Inspector (select the AccessCodeManager object).
[Serializable]
public class AccessRoom
{
    public string roomName = "ROOM";
    [Tooltip("Exactly six digits, for example 482913")]
    public string code = "000000";
    [Tooltip("Scene to load after the vault opens. It must be in Build Settings.")]
    public string destinationScene = "Room_Placeholder";
    public AccessStatus status = AccessStatus.Locked;
    [Tooltip("Tick this on ONE pedestal: it then checks the 6-digit code generated on the website (and starts the session) instead of the code above.")]
    public bool useWebsiteCode = false;
}

// Holds the room names, six-digit codes, destination scenes and access status for every pedestal.
// Nothing is hardcoded: change the list in the Inspector.
public class AccessCodeManager : MonoBehaviour
{
    public const int CodeLength = 6;
    public static AccessCodeManager Instance { get; private set; }

    public List<AccessRoom> rooms = new List<AccessRoom>();

    void Awake()
    {
        Instance = this;
        for (int i = 0; i < rooms.Count; i++)
        {
            string c = rooms[i].code;
            bool ok = c != null && c.Length == CodeLength;
            if (ok) foreach (char ch in c) if (ch < '0' || ch > '9') ok = false;
            if (!ok) Debug.LogWarning("AccessCodeManager: the code for '" + rooms[i].roomName + "' must be exactly six digits (it is '" + c + "').");
        }
    }

    void OnDestroy() { if (Instance == this) Instance = null; }

    public AccessRoom Get(int index) { return index >= 0 && index < rooms.Count ? rooms[index] : null; }

    public bool Check(int index, string entered)
    {
        AccessRoom r = Get(index);
        return r != null && entered != null && entered.Length == CodeLength && entered == r.code;
    }

    public void Grant(int index)
    {
        AccessRoom r = Get(index);
        if (r != null) r.status = AccessStatus.Granted;
    }
}
