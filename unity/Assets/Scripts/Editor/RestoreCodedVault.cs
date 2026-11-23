#if UNITY_EDITOR
using UnityEditor;
using UnityEditor.SceneManagement;
using UnityEngine;
using UnityEngine.SceneManagement;

// Menu: The Last Protocol > 8 - Restore Original Vault
// Brings back the coded vault door and hides the downloaded one (kept in the scene, switched off, for later).
public static class RestoreCodedVault
{
    [MenuItem("The Last Protocol/8 - Restore Original Vault")]
    static void Restore()
    {
        Transform door = null;
        foreach (Transform t in Resources.FindObjectsOfTypeAll<Transform>())
            if (t.name == "DoorAssembly" && t.gameObject.scene.IsValid()) { door = t; break; }
        if (door == null) { Debug.LogError("DoorAssembly not found. Run step 3 (Build Vault Sequence) instead."); return; }

        door.gameObject.SetActive(true);
        Transform root = door.parent;
        if (root != null)
            foreach (Renderer r in root.GetComponentsInChildren<Renderer>(true))
                if (!r.enabled) { r.enabled = true; Debug.Log("Restored: " + r.name); }

        VaultSequence seq = Object.FindFirstObjectByType<VaultSequence>();
        if (seq != null)
        {
            SerializedObject so = new SerializedObject(seq);
            SerializedProperty p = so.FindProperty("doorAssembly");
            if (p != null) { p.objectReferenceValue = door; so.ApplyModifiedProperties(); }
        }

        GameObject glb = GameObject.Find("GlbVault");
        if (glb != null) glb.SetActive(false);

        EditorSceneManager.MarkSceneDirty(SceneManager.GetActiveScene());
        EditorSceneManager.SaveScene(SceneManager.GetActiveScene());
        Debug.Log("Original vault restored. The downloaded vault is switched off (object 'GlbVault').");
    }
}
#endif
