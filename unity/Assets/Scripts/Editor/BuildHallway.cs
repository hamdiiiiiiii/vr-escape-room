#if UNITY_EDITOR
using System.Collections.Generic;
using System.IO;
using UnityEditor;
using UnityEditor.SceneManagement;
using UnityEngine;
using UnityEngine.SceneManagement;

// Menu: The Last Protocol > 6 - Build Hallway        (run with MainMenu open)
// Menu: The Last Protocol > 7 - Build Lab Entrance    (run with EscapeRoom_Main open, AFTER step 6)
//
// Step 6 cuts a doorway behind the vault door and builds a narrow hallway there.
// Step 7 builds the SAME hallway on the lab's entrance wall and starts the player inside it.
// When you reach the end of the menu hallway the lab loads (already prepared in the background) and you are standing
// in the identical hallway, so you simply keep walking into the room. No fade, no black screen.
// Both steps are safe to run again.
public static class BuildHallway
{
    const string MenuScenePath = "Assets/Scenes 1/MainMenu.unity";
    const string LabScenePath = "Assets/Scenes 1/EscapeRoom_Main.unity";

    // numbers (metres)
    const float FloorY = 0f;          // same height as the hall floor: no ramp, no steps
    const float Height = 2.6f;        // clear height
    const float HalfW = 1.0f;         // clear half width (2 m: narrow)
    const float HoleHalf = 1.15f;     // half width of the doorway cut in the walls
    const float Len = 7f;             // hallway length (short)
    const float TriggerBefore = 1.0f; // the scene switches this far before the end of the menu hallway
    const float MenuStartZ = 8.5f;    // menu hallway starts at the vault mount plate
    const float LabEndZ = -9.0f;      // lab hallway ends at the inner face of the lab's front wall
    const float VaultCenterY = 1.95f; // vault lowered so the circular door sits on the floor (was 2.6)

    static readonly Color Amber = new Color(1f, 0.55f, 0.12f);

    // ------------------------------------------------------------------ 6: menu side

    [MenuItem("The Last Protocol/6 - Build Hallway")]
    static void BuildMenu()
    {
        if (SceneManager.GetActiveScene().path != MenuScenePath)
        {
            EditorUtility.DisplayDialog("Open MainMenu first", "Open Assets/Scenes 1/MainMenu.unity, then run this again.", "OK");
            return;
        }
        Transform vault = FindAny("VaultDoor");
        Transform mount = FindAny("MountPlate");
        Transform wallBack = FindAny("Wall_Back");
        Transform rig = FindAny("XR Origin Hands (XR Rig)");
        if (vault == null || mount == null || wallBack == null)
        {
            EditorUtility.DisplayDialog("Missing parts", "Could not find VaultDoor / MountPlate / Wall_Back. Is this the MainMenu scene built by steps 1-3?", "OK");
            return;
        }
        VaultSequence seq = vault.GetComponent<VaultSequence>();
        if (seq == null)
        {
            EditorUtility.DisplayDialog("Missing step", "VaultDoor has no VaultSequence. Run '3 - Build Vault Sequence' (and '5 - Make Menu VR') first.", "OK");
            return;
        }

        Cleanup(mount, wallBack);
        // the 'VAULT 07 - SEALED' sign is painted on the wall right where the doorway is: move it beside the door
        Transform sign = FindAny("Sign_Vault");
        if (sign != null) sign.position = new Vector3(4.6f, sign.position.y, sign.position.z);
        vault.position = new Vector3(vault.position.x, VaultCenterY, vault.position.z);   // door now sits on the floor
        CutHole(mount, "MountPlate");
        CutHole(wallBack, "Wall_Back");
        ClearDecals(wallBack.position.z - 0.2f);

        GameObject root = new GameObject("Hallway");
        BuildCorridor(root.transform, MenuStartZ, false);     // open end: you can see the room ahead
        float zEnd = MenuStartZ + Len;
        BuildRoomPreview(zEnd);
        GameObject exit = new GameObject("ExitPoint");
        exit.transform.SetParent(root.transform, false);
        exit.transform.position = new Vector3(0, FloorY, zEnd);

        HallwayExit he = root.AddComponent<HallwayExit>();
        SerializedObject hso = new SerializedObject(he);
        hso.FindProperty("sceneName").stringValue = seq.sceneName;
        hso.FindProperty("exitPoint").objectReferenceValue = exit.transform;
        hso.FindProperty("triggerDistance").floatValue = TriggerBefore;
        hso.FindProperty("fadeSeconds").floatValue = 0f;
        SerializedProperty fq = new SerializedObject(seq).FindProperty("fadeQuad");
        hso.FindProperty("fadeQuad").objectReferenceValue = fq != null ? fq.objectReferenceValue : null;
        List<Object> hide = new List<Object>();
        foreach (string n in new[] { "HoleDark", "AmberHalo", "BehindGlow" }) { Transform t = Deep(vault, n); if (t != null) hide.Add(t.gameObject); }
        SerializedProperty arr = hso.FindProperty("hideWhenOpen");
        arr.arraySize = hide.Count;
        for (int i = 0; i < hide.Count; i++) arr.GetArrayElementAtIndex(i).objectReferenceValue = hide[i];
        hso.ApplyModifiedProperties();

        SerializedObject sso = new SerializedObject(seq);
        sso.FindProperty("hallwayExit").objectReferenceValue = he;
        Transform loco = rig != null ? Deep(rig, "Locomotion") : null;
        sso.FindProperty("locomotion").objectReferenceValue = loco != null ? loco.gameObject : null;
        sso.ApplyModifiedProperties();
        if (loco != null) loco.gameObject.SetActive(false);   // switched on when the door has opened

        EditorSceneManager.MarkSceneDirty(SceneManager.GetActiveScene());
        EditorSceneManager.SaveScene(SceneManager.GetActiveScene());
        Debug.Log("Menu hallway built and saved. Next: open EscapeRoom_Main and run 'The Last Protocol > 7 - Build Lab Entrance'.");
    }

    // ------------------------------------------------------------------ 7: lab side

    [MenuItem("The Last Protocol/7 - Build Lab Entrance")]
    static void BuildLab()
    {
        if (SceneManager.GetActiveScene().path != LabScenePath)
        {
            EditorUtility.DisplayDialog("Open the lab first", "Open Assets/Scenes 1/EscapeRoom_Main.unity, then run this again.", "OK");
            return;
        }
        Transform wallFront = FindAny("Wall_Front");
        Transform rig = FindAny("XR Origin Hands (XR Rig)");
        if (wallFront == null || rig == null)
        {
            EditorUtility.DisplayDialog("Missing parts", "Could not find Wall_Front or the XR Origin in the lab scene.", "OK");
            return;
        }

        Cleanup(wallFront, null);
        CutHole(wallFront, "Wall_Front");
        ClearDecals(wallFront.position.z + 0.2f);

        float startZ = LabEndZ - Len;
        GameObject root = new GameObject("Hallway");
        BuildCorridor(root.transform, startZ, false);

        // the player starts in the hallway at the same spot where the menu hallway ended, facing the lab
        float spawnZ = startZ + (Len - TriggerBefore);
        rig.position = new Vector3(0f, 0f, spawnZ);
        rig.rotation = Quaternion.identity;
        LabSpawn ls = rig.GetComponent<LabSpawn>();
        if (ls == null) ls = rig.gameObject.AddComponent<LabSpawn>();
        ls.spawn = new Vector3(0f, 0f, spawnZ);

        // no black fade-in: the screen must stay visible when you arrive
        SceneTransitionManager stm = Object.FindFirstObjectByType<SceneTransitionManager>();
        if (stm != null)
        {
            SerializedObject so = new SerializedObject(stm);
            so.FindProperty("fadeInOnStart").boolValue = false;
            so.ApplyModifiedProperties();
        }
        // walking must work in the lab
        Transform loco = Deep(rig, "Locomotion");
        if (loco != null) loco.gameObject.SetActive(true);

        EditorSceneManager.MarkSceneDirty(SceneManager.GetActiveScene());
        EditorSceneManager.SaveScene(SceneManager.GetActiveScene());
        Debug.Log("Lab entrance built and saved: the player starts in the hallway at z=" + spawnZ + ". Open MainMenu and press Play to try the whole walk.");
    }

    // The lab's visible parts (hall, props, lights) copied into the menu scene at the end of the hallway, so what you see
    // ahead is the real lab and the scene switch is invisible. Everything that thinks (scripts, sounds, cameras,
    // physics) is removed from the copy. Needs the lab scene on disk; run step 7 first for the best result.
    static void BuildRoomPreview(float hallwayEndZ)
    {
        Scene menu = SceneManager.GetActiveScene();
        Scene lab = EditorSceneManager.OpenScene(LabScenePath, OpenSceneMode.Additive);
        string[] wanted = { "MenuEnvironment", "LabProps", "TestingDetails", "Pass2Details", "Pass2Lights", "LabFillLights", "LightBeams", "Pass2Mist" };
        string[] kill = { "VaultSequence", "MenuManager", "AccessPedestal", "AccessCodeManager", "KeypadController", "LabSessionBridge",
                          "PlayerInteraction", "SceneTransitionManager", "HallwayExit", "MenuPlacement", "ReturnToScene", "ReturnToMenu",
                          "MenuButton", "EventSystem", "StandaloneInputModule", "InputSystemUIInputModule", "PlayerHeightFix", "LabSpawn" };

        GameObject previewRoot = new GameObject("LabPreview");
        SceneManager.MoveGameObjectToScene(previewRoot, menu);
        previewRoot.transform.position = new Vector3(0f, 0f, hallwayEndZ + 9f);     // the lab's front wall (z = -9) meets the hallway end

        int copied = 0;
        foreach (GameObject root in lab.GetRootGameObjects())
        {
            if (System.Array.IndexOf(wanted, root.name) < 0) continue;
            GameObject clone = Object.Instantiate(root);
            clone.name = root.name;
            SceneManager.MoveGameObjectToScene(clone, menu);
            clone.transform.SetParent(previewRoot.transform, false);
            copied++;
        }
        EditorSceneManager.CloseScene(lab, true);
        if (copied == 0) { Debug.LogWarning("Hallway: found nothing to copy from the lab scene."); return; }

        // strip everything that is not just looks
        foreach (MonoBehaviour mb in previewRoot.GetComponentsInChildren<MonoBehaviour>(true))
            if (mb != null && System.Array.IndexOf(kill, mb.GetType().Name) >= 0) Object.DestroyImmediate(mb);
        foreach (Camera c in previewRoot.GetComponentsInChildren<Camera>(true)) Object.DestroyImmediate(c);
        foreach (AudioListener a in previewRoot.GetComponentsInChildren<AudioListener>(true)) Object.DestroyImmediate(a);
        foreach (AudioSource a in previewRoot.GetComponentsInChildren<AudioSource>(true)) Object.DestroyImmediate(a);
        foreach (UnityEngine.Rendering.Volume v in previewRoot.GetComponentsInChildren<UnityEngine.Rendering.Volume>(true)) Object.DestroyImmediate(v);
        foreach (Collider c in previewRoot.GetComponentsInChildren<Collider>(true)) if (c != null && c.gameObject.name != "Floor") Object.DestroyImmediate(c);

        // if the lab's front wall is not cut yet (step 7 not run), open it here
        Transform front = Deep(previewRoot.transform, "Wall_Front");
        if (front != null && front.gameObject.activeSelf)
        {
            CutHole(front, "PreviewFront");
            foreach (Collider c in front.parent.GetComponentsInChildren<Collider>(true)) if (c != null && c.gameObject.name.StartsWith("HallCut_")) Object.DestroyImmediate(c);
        }
        Debug.Log("Hallway: copied " + copied + " lab parts behind the hallway end.");
    }

    // Thin decal sheets (panel seams, rust, grime) lie flat on the walls. The one on the doorway wall would now
    // hang across the doorway like a see-through film, so every thin sheet that touches the doorway is switched off.
    static void ClearDecals(float wallZ)
    {
        Bounds door = new Bounds(new Vector3(0f, FloorY + Height / 2f, wallZ), new Vector3(2.3f, Height, 1.0f));
        int n = 0;
        foreach (Renderer r in Object.FindObjectsByType<Renderer>(FindObjectsInactive.Include, FindObjectsSortMode.None))
        {
            if (!(r is MeshRenderer) || !r.enabled) continue;
            Transform t = r.transform;
            bool skip = false;
            for (Transform p = t; p != null; p = p.parent)
                if (p.name == "VaultDoor" || p.name == "Hallway" || p.name == "LabPreview" || p.name.StartsWith("HallCut_")) { skip = true; break; }
            if (skip) continue;
            Bounds b = r.bounds;
            float thin = Mathf.Min(b.size.x, Mathf.Min(b.size.y, b.size.z));
            if (thin < 0.08f && b.Intersects(door)) { r.enabled = false; n++; Debug.Log("Hallway: switched off decal '" + t.name + "' (it covered the doorway)."); }
        }
        if (n == 0) Debug.Log("Hallway: no decal sheets were covering the doorway.");
    }

    // ------------------------------------------------------------------ the corridor (same in both scenes)

    static void BuildCorridor(Transform h, float startZ, bool closedEnd)
    {
        Material steel = Mat("M_Steel"), dark = Mat("M_SteelDark"), black = Mat("M_Black");
        if (steel == null) steel = Plain("M_HallSteel", new Color(0.35f, 0.37f, 0.4f), Color.black);
        if (dark == null) dark = Plain("M_HallDark", new Color(0.12f, 0.13f, 0.15f), Color.black);
        if (black == null) black = Plain("M_HallBlack", new Color(0.02f, 0.02f, 0.025f), Color.black);
        Material strip = Plain("M_HallStrip", new Color(0.1f, 0.05f, 0.02f), Amber * 3.2f);

        float cy = FloorY + Height / 2f, zc = startZ + Len / 2f, zEnd = startZ + Len;
        float width = HoleHalf * 2f;

        Box("Floor", h, new Vector3(0, FloorY - 0.1f, zc), new Vector3(width, 0.2f, Len), dark, true);
        Box("Ceiling", h, new Vector3(0, FloorY + Height + 0.1f, zc), new Vector3(width, 0.2f, Len), dark, true);
        Box("Wall_L", h, new Vector3(-(HalfW + 0.075f), cy, zc), new Vector3(0.15f, Height + 0.4f, Len), steel, true);
        Box("Wall_R", h, new Vector3(HalfW + 0.075f, cy, zc), new Vector3(0.15f, Height + 0.4f, Len), steel, true);
        if (closedEnd) Box("EndWall", h, new Vector3(0, cy, zEnd + 0.1f), new Vector3(width, Height + 0.4f, 0.2f), black, true);

        for (float o = 1.5f; o < Len - 0.5f; o += 2f)
        {
            float z = startZ + o;
            Box("Rib_L", h, new Vector3(-(HalfW - 0.04f), cy, z), new Vector3(0.1f, Height, 0.3f), dark, false);
            Box("Rib_R", h, new Vector3(HalfW - 0.04f, cy, z), new Vector3(0.1f, Height, 0.3f), dark, false);
            Box("Rib_T", h, new Vector3(0, FloorY + Height - 0.05f, z), new Vector3(width - 0.3f, 0.1f, 0.3f), dark, false);
        }
        Box("Strip_CeilL", h, new Vector3(-0.8f, FloorY + Height - 0.04f, zc), new Vector3(0.06f, 0.05f, Len - 0.4f), strip, false);
        Box("Strip_CeilR", h, new Vector3(0.8f, FloorY + Height - 0.04f, zc), new Vector3(0.06f, 0.05f, Len - 0.4f), strip, false);
        Box("Strip_FloorL", h, new Vector3(-0.92f, FloorY + 0.02f, zc), new Vector3(0.05f, 0.04f, Len - 0.4f), strip, false);
        Box("Strip_FloorR", h, new Vector3(0.92f, FloorY + 0.02f, zc), new Vector3(0.05f, 0.04f, Len - 0.4f), strip, false);

        AddLight(h, "HallLight1", new Vector3(0, FloorY + Height - 0.4f, startZ + 1.8f), Amber, 2.2f, 6f);
        AddLight(h, "HallLight2", new Vector3(0, FloorY + Height - 0.4f, startZ + 5.2f), Amber, 2.2f, 6f);
    }

    // ------------------------------------------------------------------ helpers

    static void Cleanup(Transform a, Transform b)
    {
        Transform old = FindAny("Hallway"); if (old != null) Object.DestroyImmediate(old.gameObject);
        Transform prev = FindAny("LabPreview"); if (prev != null) Object.DestroyImmediate(prev.gameObject);
        Transform ramp = FindAny("VaultRamp"); if (ramp != null) Object.DestroyImmediate(ramp.gameObject);
        List<Transform> cuts = new List<Transform>();
        CollectByPrefix(a.parent, "HallCut_", cuts);
        if (b != null) CollectByPrefix(b.parent, "HallCut_", cuts);
        foreach (Transform c in cuts) if (c != null) Object.DestroyImmediate(c.gameObject);
        a.gameObject.SetActive(true);
        if (b != null) b.gameObject.SetActive(true);
    }

    // replaces a solid cube wall by four pieces around a doorway (the original is only switched off)
    static void CutHole(Transform orig, string label)
    {
        Vector3 c = orig.position, s = orig.lossyScale;
        Material m = orig.GetComponent<Renderer>().sharedMaterial;
        float x0 = c.x - s.x / 2f, x1 = c.x + s.x / 2f, y0 = c.y - s.y / 2f, y1 = c.y + s.y / 2f;
        float top = FloorY + Height;
        Piece(orig, label + "_L", m, x0, -HoleHalf, y0, y1, c.z, s.z);
        Piece(orig, label + "_R", m, HoleHalf, x1, y0, y1, c.z, s.z);
        Piece(orig, label + "_Below", m, -HoleHalf, HoleHalf, y0, FloorY, c.z, s.z);
        Piece(orig, label + "_Above", m, -HoleHalf, HoleHalf, top, y1, c.z, s.z);
        orig.gameObject.SetActive(false);
    }

    static void Piece(Transform orig, string name, Material m, float xa, float xb, float ya, float yb, float z, float depth)
    {
        if (xb - xa < 0.01f || yb - ya < 0.01f) return;
        GameObject go = GameObject.CreatePrimitive(PrimitiveType.Cube);
        go.name = "HallCut_" + name;
        go.transform.SetParent(orig.parent, true);
        go.transform.position = new Vector3((xa + xb) / 2f, (ya + yb) / 2f, z);
        go.transform.rotation = Quaternion.identity;
        go.transform.localScale = new Vector3(xb - xa, yb - ya, depth);
        go.GetComponent<Renderer>().sharedMaterial = m;
    }

    static GameObject Box(string name, Transform parent, Vector3 center, Vector3 size, Material m, bool collider)
    {
        GameObject go = GameObject.CreatePrimitive(PrimitiveType.Cube);
        go.name = name;
        go.transform.SetParent(parent, false);
        go.transform.position = center;
        go.transform.localScale = size;
        if (m != null) go.GetComponent<Renderer>().sharedMaterial = m;
        if (!collider) Object.DestroyImmediate(go.GetComponent<Collider>());
        return go;
    }

    static void AddLight(Transform parent, string name, Vector3 pos, Color color, float intensity, float range)
    {
        GameObject go = new GameObject(name);
        go.transform.SetParent(parent, false);
        go.transform.position = pos;
        Light l = go.AddComponent<Light>();
        l.type = LightType.Point; l.color = color; l.intensity = intensity; l.range = range; l.shadows = LightShadows.None;
    }

    static Material Plain(string name, Color color, Color emission)
    {
        string path = "Assets/MainMenu/Materials/" + name + ".mat";
        Material m = AssetDatabase.LoadAssetAtPath<Material>(path);
        if (m == null)
        {
            if (!AssetDatabase.IsValidFolder("Assets/MainMenu/Materials")) Directory.CreateDirectory("Assets/MainMenu/Materials");
            m = new Material(Shader.Find("Universal Render Pipeline/Lit"));
            AssetDatabase.CreateAsset(m, path);
        }
        m.SetColor("_BaseColor", color);
        m.SetFloat("_Metallic", 0.5f); m.SetFloat("_Smoothness", 0.35f);
        if (emission.maxColorComponent > 0.01f)
        {
            m.EnableKeyword("_EMISSION");
            m.globalIlluminationFlags = MaterialGlobalIlluminationFlags.None;
            m.SetColor("_EmissionColor", emission);
        }
        EditorUtility.SetDirty(m);
        return m;
    }

    static Material Mat(string name)
    {
        foreach (string guid in AssetDatabase.FindAssets(name + " t:Material"))
        {
            string p = AssetDatabase.GUIDToAssetPath(guid);
            if (Path.GetFileNameWithoutExtension(p) == name) return AssetDatabase.LoadAssetAtPath<Material>(p);
        }
        return null;
    }

    static Transform FindAny(string name)
    {
        foreach (GameObject r in SceneManager.GetActiveScene().GetRootGameObjects())
        {
            Transform t = Deep(r.transform, name);
            if (t != null) return t;
        }
        return null;
    }

    static Transform Deep(Transform t, string name)
    {
        if (t.name == name) return t;
        foreach (Transform c in t) { Transform r = Deep(c, name); if (r != null) return r; }
        return null;
    }

    static void CollectByPrefix(Transform parent, string prefix, List<Transform> list)
    {
        if (parent == null) return;
        foreach (Transform c in parent) if (c.name.StartsWith(prefix)) list.Add(c);
    }
}
#endif
