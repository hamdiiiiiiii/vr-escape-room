#if UNITY_EDITOR
using System;
using TMPro;
using UnityEditor;
using UnityEditor.SceneManagement;
using UnityEngine;
using UnityEngine.SceneManagement;
using UnityEngine.UI;

// Menu: Museum > Build Home Menu
// Builds the two screens of the home lobby:
//   - MonitorCanvas: the big wall monitor left of the vault door (welcome text, then room details + START)
//   - KeypadCanvas : the entry keypad on the wall right of the vault door
// Also wires the HomeMenu script, puts the player at the start spot and switches off the blue teleport circle.
// Safe to run again: it replaces its own objects. Run "Build Home Environment" after it.
public static class BuildHomeMenu
{
    static readonly Color PanelColor = new Color32(6, 14, 20, 255);
    static readonly Color ButtonColor = new Color32(18, 52, 60, 255);
    static readonly Color TextColor = new Color32(220, 235, 240, 255);
    static readonly Color AccentColor = new Color32(63, 224, 197, 255);
    static readonly Color OrangeColor = new Color32(255, 138, 61, 255);
    static readonly Color GreenColor = new Color32(74, 222, 128, 255);

    // Shared with BuildHomeEnvironment (the physical keypad box and monitor frame sit behind these canvases)
    static readonly Vector3 MonitorPos = new Vector3(-3.6f, 2.0f, 4.78f);
    static readonly Vector3 KeypadPos = new Vector3(3.4f, 1.4f, 4.75f);

    [MenuItem("Museum/Build Home Menu")]
    static void Build()
    {
        DestroyIfExists("HomeCanvas");      // from the first version
        DestroyIfExists("MonitorCanvas");
        DestroyIfExists("KeypadCanvas");
        DestroyIfExists("HomeMenu");

        PlacePlayer();

        // ---------- Wall monitor (1200 x 700 px, 2 mm per px = 2.4 x 1.4 m) ----------
        RectTransform monitor = NewCanvas("MonitorCanvas", MonitorPos, new Vector2(1200, 700));
        RectTransform border = NewRect("Border", monitor);
        Stretch(border);
        border.gameObject.AddComponent<Image>().color = AccentColor;
        RectTransform inner = NewRect("Screen", monitor);
        Stretch(inner, 6);
        inner.gameObject.AddComponent<Image>().color = PanelColor;

        RectTransform welcome = NewRect("WelcomePanel", monitor); Stretch(welcome);
        RectTransform confirm = NewRect("ConfirmPanel", monitor); Stretch(confirm);

        NewText("Tag", welcome, "// SECURE FACILITY  -  LEVEL 5", 30, new Vector2(0, 280), new Vector2(1100, 40), OrangeColor);
        NewText("Title", welcome, "BUGHUNT VR", 150, new Vector2(0, 130), new Vector2(1100, 180), AccentColor);
        NewText("Subtitle", welcome, "FIND THE BUGS. ESCAPE THE ROOM.", 44, new Vector2(0, 10), new Vector2(1100, 70), TextColor);
        NewText("Instruction", welcome, "> ENTER YOUR 6-DIGIT CODE ON THE KEYPAD", 36, new Vector2(0, -150), new Vector2(1100, 60), OrangeColor);
        NewText("Footer", welcome, "SYSTEM LOCKED  |  AWAITING CODE", 28, new Vector2(0, -280), new Vector2(1100, 40), new Color32(255, 93, 93, 255));

        NewText("Granted", confirm, "ACCESS GRANTED", 100, new Vector2(0, 200), new Vector2(1100, 130), GreenColor);
        TMP_Text roomName = NewText("RoomNameText", confirm, "Room name", 76, new Vector2(0, 60), new Vector2(1100, 110), TextColor);
        TMP_Text roomInfo = NewText("RoomInfoText", confirm, "TIME LIMIT", 40, new Vector2(0, -30), new Vector2(1100, 60), AccentColor);
        Button start = NewButton("StartButton", confirm, "START", new Vector2(0, -190), new Vector2(520, 130), 70);

        // ---------- Wall keypad (320 x 540 px = 0.64 x 1.08 m) ----------
        RectTransform pad = NewCanvas("KeypadCanvas", KeypadPos, new Vector2(320, 540));
        RectTransform padBg = NewRect("Background", pad); Stretch(padBg);
        padBg.gameObject.AddComponent<Image>().color = new Color32(8, 14, 18, 255);

        // little display
        RectTransform display = NewRect("Display", pad);
        display.anchoredPosition = new Vector2(0, 218);
        display.sizeDelta = new Vector2(290, 70);
        display.gameObject.AddComponent<Image>().color = new Color32(2, 20, 16, 255);
        TMP_Text codeText = NewText("CodeText", display, "- - - - - -", 42, Vector2.zero, new Vector2(290, 70), AccentColor);
        TMP_Text messageText = NewText("MessageText", pad, "ENTER THE 6-DIGIT CODE", 19, new Vector2(0, 160), new Vector2(300, 40), AccentColor);

        RectTransform keypad = NewRect("Keypad", pad);
        keypad.anchoredPosition = new Vector2(0, -42);
        keypad.sizeDelta = new Vector2(290, 340);
        GridLayoutGroup grid = keypad.gameObject.AddComponent<GridLayoutGroup>();
        grid.cellSize = new Vector2(90, 78);
        grid.spacing = new Vector2(10, 8);
        grid.constraint = GridLayoutGroup.Constraint.FixedColumnCount;
        grid.constraintCount = 3;
        grid.childAlignment = TextAnchor.MiddleCenter;
        Button keyTemplate = NewButton("KeyTemplate", keypad, "0", Vector2.zero, new Vector2(90, 78), 38);

        // ---------- Wire up the script ----------
        GameObject menuGo = new GameObject("HomeMenu");
        Undo.RegisterCreatedObjectUndo(menuGo, "Build Home Menu");
        HomeMenu menu = menuGo.AddComponent<HomeMenu>();
        SerializedObject so = new SerializedObject(menu);
        Set(so, "welcomePanel", welcome.gameObject);
        Set(so, "confirmPanel", confirm.gameObject);
        Set(so, "roomNameText", roomName);
        Set(so, "roomInfoText", roomInfo);
        Set(so, "startButton", start);
        Set(so, "codeText", codeText);
        Set(so, "messageText", messageText);
        Set(so, "keyTemplate", keyTemplate);
        Set(so, "keypadRoot", keypad);
        VaultDoorController vault = UnityEngine.Object.FindFirstObjectByType<VaultDoorController>();
        if (vault != null) Set(so, "vaultDoor", vault);
        so.ApplyModifiedProperties();

        // In the editor only the welcome screen shows; HomeMenu switches while playing.
        confirm.gameObject.SetActive(false);

        EditorSceneManager.MarkSceneDirty(SceneManager.GetActiveScene());
        Selection.activeGameObject = menuGo;
        Debug.Log("Home menu built (monitor + wall keypad). Now run Museum > Build Home Environment.");
    }

    static void PlacePlayer()
    {
        GameObject rig = GameObject.Find("XR Origin Hands (XR Rig)");
        if (rig != null)
        {
            Undo.RecordObject(rig.transform, "Place player");
            rig.transform.position = new Vector3(0f, 0f, -4f);
            rig.transform.rotation = Quaternion.identity;   // looking toward the vault door (+Z)
        }
        else Debug.LogWarning("Could not find 'XR Origin Hands (XR Rig)'. Put your player at (0,0,-4) facing the vault door by hand.");

        GameObject teleport = GameObject.Find("Teleport Area Setup");   // the blue circle
        if (teleport != null) { Undo.RecordObject(teleport, "Hide teleport"); teleport.SetActive(false); }
    }

    static RectTransform NewCanvas(string name, Vector3 pos, Vector2 size)
    {
        GameObject go = new GameObject(name, typeof(RectTransform), typeof(Canvas), typeof(CanvasScaler), typeof(GraphicRaycaster));
        Undo.RegisterCreatedObjectUndo(go, "Build Home Menu");
        go.GetComponent<Canvas>().renderMode = RenderMode.WorldSpace;
        RectTransform rt = go.GetComponent<RectTransform>();
        rt.sizeDelta = size;
        rt.localScale = Vector3.one * 0.002f;
        rt.position = pos;
        rt.rotation = Quaternion.identity;   // readable by a player looking toward +Z
        AddXrRaycaster(go);
        return rt;
    }

    static void Set(SerializedObject so, string field, UnityEngine.Object value)
    {
        SerializedProperty p = so.FindProperty(field);
        if (p == null) { Debug.LogWarning("HomeMenu has no field called " + field); return; }
        p.objectReferenceValue = value;
    }

    static void DestroyIfExists(string name)
    {
        GameObject old = GameObject.Find(name);
        while (old != null) { Undo.DestroyObjectImmediate(old); old = GameObject.Find(name); }
    }

    // The XR pointer needs this to click world-space UI. Found by name so the script compiles without it.
    static void AddXrRaycaster(GameObject go)
    {
        foreach (var asm in AppDomain.CurrentDomain.GetAssemblies())
        {
            Type t = asm.GetType("UnityEngine.XR.Interaction.Toolkit.UI.TrackedDeviceGraphicRaycaster");
            if (t != null) { go.AddComponent(t); return; }
        }
        Debug.LogWarning("TrackedDeviceGraphicRaycaster not found. Add it to " + go.name + " by hand so VR pointers can click.");
    }

    static RectTransform NewRect(string name, Transform parent)
    {
        GameObject go = new GameObject(name, typeof(RectTransform));
        go.transform.SetParent(parent, false);
        return go.GetComponent<RectTransform>();
    }

    static void Stretch(RectTransform rt, float inset = 0f)
    {
        rt.anchorMin = Vector2.zero; rt.anchorMax = Vector2.one;
        rt.offsetMin = new Vector2(inset, inset); rt.offsetMax = new Vector2(-inset, -inset);
    }

    static TMP_Text NewText(string name, Transform parent, string text, float size, Vector2 pos, Vector2 box, Color color)
    {
        RectTransform rt = NewRect(name, parent);
        rt.anchoredPosition = pos;
        rt.sizeDelta = box;
        TextMeshProUGUI t = rt.gameObject.AddComponent<TextMeshProUGUI>();
        t.text = text;
        t.fontSize = size;
        t.alignment = TextAlignmentOptions.Center;
        t.color = color;
        t.raycastTarget = false;
        return t;
    }

    static Button NewButton(string name, Transform parent, string label, Vector2 pos, Vector2 box, float fontSize)
    {
        RectTransform rt = NewRect(name, parent);
        rt.anchoredPosition = pos;
        rt.sizeDelta = box;
        Image img = rt.gameObject.AddComponent<Image>();
        img.color = ButtonColor;
        Button button = rt.gameObject.AddComponent<Button>();
        button.targetGraphic = img;
        ColorBlock cb = button.colors;
        cb.highlightedColor = new Color(0.45f, 1f, 0.9f);
        cb.pressedColor = new Color(1f, 0.6f, 0.3f);
        button.colors = cb;

        TMP_Text t = NewText("Text (TMP)", rt, label, fontSize, Vector2.zero, box, TextColor);
        Stretch(t.rectTransform);
        return button;
    }
}
#endif
