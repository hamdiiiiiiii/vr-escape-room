#if UNITY_EDITOR
using System.Collections.Generic;
using System.IO;
using TMPro;
using UnityEditor;
using UnityEditor.SceneManagement;
using UnityEngine;
using UnityEngine.EventSystems;
using UnityEngine.InputSystem.UI;
using UnityEngine.Rendering.Universal;
using UnityEngine.SceneManagement;
using UnityEngine.UI;

// Menu: The Last Protocol > 3 - Build Vault Sequence      (STAGE 5: connects Enter the Facility to the vault animation)
// Menu: The Last Protocol > 4 - Create Placeholder Scene  (makes EscapeRoom_Main if you do not have it, and fills Build Settings)
public static class BuildVaultSequence
{
    const string MenuScenePath = "Assets/Scenes 1/MainMenu.unity";
    const string GameScenePath = "Assets/Scenes 1/EscapeRoom_Main.unity";

    // ------------------------------------------------------------------ 3: vault sequence

    [MenuItem("The Last Protocol/3 - Build Vault Sequence")]
    static void BuildSequence()
    {
        if (SceneManager.GetActiveScene().path != MenuScenePath)
        {
            EditorUtility.DisplayDialog("Open MainMenu first", "Open Assets/Scenes 1/MainMenu.unity, then run this again.", "OK");
            return;
        }

        GameObject vault = GameObject.Find("VaultDoor");
        GameObject manager = GameObject.Find("MenuManager");
        GameObject canvas = GameObject.Find("MenuCanvas");
        if (vault == null || manager == null || canvas == null)
        {
            EditorUtility.DisplayDialog("Missing parts", "Run '1 - Build Environment' and '2 - Build Menu UI' first.", "OK");
            return;
        }

        // full-screen black overlay on top of the menu
        Transform old = canvas.transform.Find("FadeOverlay");
        if (old != null) Object.DestroyImmediate(old.gameObject);
        GameObject ov = new GameObject("FadeOverlay", typeof(RectTransform), typeof(CanvasGroup), typeof(Image));
        ov.transform.SetParent(canvas.transform, false);
        RectTransform ort = (RectTransform)ov.transform;
        ort.anchorMin = Vector2.zero; ort.anchorMax = Vector2.one; ort.offsetMin = Vector2.zero; ort.offsetMax = Vector2.zero;
        ov.GetComponent<Image>().color = Color.black;
        CanvasGroup fade = ov.GetComponent<CanvasGroup>();
        fade.alpha = 0f; fade.blocksRaycasts = false; fade.interactable = false;
        ov.transform.SetAsLastSibling();

        // audio source for the vault sounds
        AudioSource src = vault.GetComponent<AudioSource>();
        if (src == null) src = vault.AddComponent<AudioSource>();
        src.playOnAwake = false; src.spatialBlend = 0f;

        // the sequence component
        VaultSequence seq = vault.GetComponent<VaultSequence>();
        if (seq == null) seq = vault.AddComponent<VaultSequence>();

        Transform door = Deep(vault.transform, "DoorAssembly");
        SerializedObject so = new SerializedObject(seq);
        Ref(so, "doorAssembly", door);
        Ref(so, "wheel", Deep(vault.transform, "Wheel"));
        Ref(so, "lockBolts", Deep(vault.transform, "LockBolts"));
        Ref(so, "behindGlow", Go(Deep(vault.transform, "BehindGlow")));
        Ref(so, "behindLight", Go(Deep(vault.transform, "BehindLight")));
        Transform lamp = Deep(vault.transform, "StatusLamp");
        Ref(so, "statusLamp", lamp != null ? lamp.GetComponent<Renderer>() : null);
        Ref(so, "statusText", Component<TMP_Text>(Deep(vault.transform, "StatusText")));
        Ref(so, "glow", vault.GetComponent<VaultGlow>());
        Ref(so, "beacon", Object.FindFirstObjectByType<MenuBeacon>());
        Camera cam = Camera.main;
        Ref(so, "cam", cam);
        Ref(so, "cameraSway", cam != null ? cam.GetComponent<MenuCameraSway>() : null);
        Ref(so, "fade", fade);
        Ref(so, "audioSource", src);
        Ref(so, "grantedClip", Clip("sfx_keypadGranted"));
        Ref(so, "lockClip", Clip("734641__vrymaa__lock-strong-door"));
        Ref(so, "doorClip", Clip("426624__mrh4hn__heavy-metal-door-far-away"));
        so.FindProperty("sceneName").stringValue = "EscapeRoom_Main";
        so.ApplyModifiedProperties();

        string missing = "";
        if (door == null) missing += " DoorAssembly";
        if (Clip("734641__vrymaa__lock-strong-door") == null) missing += " lock-sound";
        if (Clip("426624__mrh4hn__heavy-metal-door-far-away") == null) missing += " door-sound";
        if (missing != "") Debug.LogWarning("Vault sequence built but these were not found:" + missing);

        EditorSceneManager.MarkSceneDirty(SceneManager.GetActiveScene());
        EditorSceneManager.SaveScene(SceneManager.GetActiveScene());
        Debug.Log("Vault sequence connected and scene saved. Next: 'The Last Protocol > 4 - Create Placeholder Scene' (if you have no EscapeRoom_Main), then press Play and click ENTER THE FACILITY.");
    }

    // ------------------------------------------------------------------ 4: placeholder scene + build settings

    [MenuItem("The Last Protocol/4 - Create Placeholder Scene")]
    static void CreatePlaceholder()
    {
        if (!EditorSceneManager.SaveCurrentModifiedScenesIfUserWantsTo()) return;

        if (!File.Exists(GameScenePath))
        {
            Scene s = EditorSceneManager.NewScene(NewSceneSetup.EmptyScene, NewSceneMode.Single);

            GameObject camGo = new GameObject("Main Camera");
            camGo.tag = "MainCamera";
            Camera cam = camGo.AddComponent<Camera>();
            cam.clearFlags = CameraClearFlags.SolidColor; cam.backgroundColor = new Color(0.02f, 0.03f, 0.04f);
            camGo.AddComponent<AudioListener>();
            camGo.AddComponent<UniversalAdditionalCameraData>();
            camGo.transform.position = new Vector3(0, 1.7f, -3f);

            GameObject floor = GameObject.CreatePrimitive(PrimitiveType.Cube);
            floor.name = "Floor"; floor.transform.position = new Vector3(0, -0.05f, 0); floor.transform.localScale = new Vector3(12, 0.1f, 12);
            GameObject lightGo = new GameObject("Light");
            Light l = lightGo.AddComponent<Light>();
            l.type = LightType.Directional; l.intensity = 0.8f; lightGo.transform.rotation = Quaternion.Euler(50, -30, 0);

            GameObject cg = new GameObject("Canvas", typeof(RectTransform), typeof(Canvas), typeof(CanvasScaler), typeof(GraphicRaycaster));
            Canvas canvas = cg.GetComponent<Canvas>(); canvas.renderMode = RenderMode.ScreenSpaceOverlay;
            CanvasScaler sc = cg.GetComponent<CanvasScaler>();
            sc.uiScaleMode = CanvasScaler.ScaleMode.ScaleWithScreenSize; sc.referenceResolution = new Vector2(1920, 1080);

            TMP_Text msg = NewText(cg.transform, "Message",
                "ESCAPE ROOM  -  PLACEHOLDER\n\nThe real gameplay scene goes here.\nPress ESC (or click the button) to return to the main menu.", 44, new Vector2(0, 120), new Vector2(1500, 400));

            GameObject bg = new GameObject("ReturnButton", typeof(RectTransform), typeof(Image), typeof(Button));
            bg.transform.SetParent(cg.transform, false);
            RectTransform brt = (RectTransform)bg.transform; brt.sizeDelta = new Vector2(420, 80); brt.anchoredPosition = new Vector2(0, -200);
            bg.GetComponent<Image>().color = new Color(0.9f, 0.45f, 0.1f);
            NewText(bg.transform, "Text", "RETURN TO MENU", 32, Vector2.zero, new Vector2(420, 80)).color = Color.black;

            GameObject es = new GameObject("EventSystem", typeof(EventSystem));
            es.AddComponent<InputSystemUIInputModule>().AssignDefaultActions();

            GameObject logic = new GameObject("ReturnToMenu");
            ReturnToMenu rm = logic.AddComponent<ReturnToMenu>();
            UnityEditor.Events.UnityEventTools.AddPersistentListener(bg.GetComponent<Button>().onClick, rm.Go);

            Directory.CreateDirectory(Path.GetDirectoryName(GameScenePath));
            EditorSceneManager.SaveScene(s, GameScenePath);
            Debug.Log("Placeholder scene created: " + GameScenePath);
        }
        else Debug.Log("EscapeRoom_Main.unity already exists. It was not changed.");

        AddToBuildSettings();
        EditorSceneManager.OpenScene(MenuScenePath);
    }

    static void AddToBuildSettings()
    {
        List<EditorBuildSettingsScene> scenes = new List<EditorBuildSettingsScene>(EditorBuildSettings.scenes);
        bool hasMenu = scenes.Exists(s => s.path == MenuScenePath);
        bool hasGame = scenes.Exists(s => s.path == GameScenePath);
        if (!hasMenu) scenes.Insert(0, new EditorBuildSettingsScene(MenuScenePath, true));   // first = the scene a build starts with
        if (!hasGame) scenes.Add(new EditorBuildSettingsScene(GameScenePath, true));
        EditorBuildSettings.scenes = scenes.ToArray();
        Debug.Log("Build Settings now start with MainMenu and include EscapeRoom_Main. (This is a shared file: tell Member 4.)");
    }

    // ------------------------------------------------------------------ helpers

    static TMP_Text NewText(Transform parent, string name, string text, float size, Vector2 pos, Vector2 box)
    {
        GameObject go = new GameObject(name, typeof(RectTransform));
        go.transform.SetParent(parent, false);
        RectTransform rt = (RectTransform)go.transform; rt.anchoredPosition = pos; rt.sizeDelta = box;
        TextMeshProUGUI t = go.AddComponent<TextMeshProUGUI>();
        t.text = text; t.fontSize = size; t.alignment = TextAlignmentOptions.Center; t.color = Color.white; t.raycastTarget = false;
        return t;
    }

    static Transform Deep(Transform root, string name)
    {
        if (root == null) return null;
        foreach (Transform c in root)
        {
            if (c.name == name) return c;
            Transform r = Deep(c, name);
            if (r != null) return r;
        }
        return null;
    }

    static GameObject Go(Transform t) { return t != null ? t.gameObject : null; }
    static T Component<T>(Transform t) where T : Component { return t != null ? t.GetComponent<T>() : null; }

    static void Ref(SerializedObject so, string field, Object value)
    {
        SerializedProperty p = so.FindProperty(field);
        if (p == null) { Debug.LogWarning("Missing field: " + field); return; }
        p.objectReferenceValue = value;
    }

    static AudioClip Clip(string name)
    {
        foreach (string guid in AssetDatabase.FindAssets(name + " t:AudioClip"))
        {
            string p = AssetDatabase.GUIDToAssetPath(guid);
            if (Path.GetFileNameWithoutExtension(p) == name) return AssetDatabase.LoadAssetAtPath<AudioClip>(p);
        }
        return null;
    }
}
#endif
