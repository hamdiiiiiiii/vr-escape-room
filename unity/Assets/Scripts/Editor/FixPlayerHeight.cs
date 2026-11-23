#if UNITY_EDITOR
using Unity.XR.CoreUtils;
using UnityEditor;
using UnityEditor.SceneManagement;
using UnityEngine;
using UnityEngine.SceneManagement;

// Menu: The Last Protocol > L3 - Fix Player Height
// Puts the player's eyes at a normal standing height (1.6 m) in the scene that is open.
public static class FixPlayerHeight
{
    [MenuItem("The Last Protocol/L3 - Fix Player Height")]
    static void Fix()
    {
        XROrigin origin = Object.FindFirstObjectByType<XROrigin>();
        if (origin == null) { Debug.LogError("No XR Origin in this scene."); return; }
        origin.RequestedTrackingOriginMode = XROrigin.TrackingOriginMode.Device;   // the origin adds the height below
        origin.CameraYOffset = 1.6f;
        if (origin.GetComponent<PlayerHeightFix>() == null) origin.gameObject.AddComponent<PlayerHeightFix>();
        EditorUtility.SetDirty(origin);
        EditorSceneManager.MarkSceneDirty(SceneManager.GetActiveScene());
        EditorSceneManager.SaveScene(SceneManager.GetActiveScene());
        Debug.Log("Player eye height set to 1.6 m (Tracking Origin Mode = Device). Press Play.");
    }
}
#endif
