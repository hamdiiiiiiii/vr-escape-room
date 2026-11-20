#if UNITY_EDITOR
using System.IO;
using TMPro;
using UnityEditor;
using UnityEditor.SceneManagement;
using UnityEngine;
using UnityEngine.EventSystems;
using UnityEngine.InputSystem.UI;
using UnityEngine.SceneManagement;
using UnityEngine.UI;

// Menu: The Last Protocol > 2 - Build Menu UI
// STAGES 3 + 4: builds the title, subtitle, four buttons, How To Play and Settings panels,
// and connects them to MenuManager / SettingsPanel. Run it with the MainMenu scene open.
public static class BuildMainMenuUI
{
    const string Generated = "Assets/MainMenu/Generated";
    const string ScenePath = "Assets/Scenes 1/MainMenu.unity";
    const string FontFolder = "Assets/MainMenu/Imported/Orbitron/static/";

    static readonly Color Amber = new Color(1f, 0.58f, 0.14f);
    static readonly Color Pale = new Color(0.86f, 0.9f, 0.94f);
    static readonly Color Dim = new Color(0.55f, 0.6f, 0.66f);
    static readonly Color Glass = new Color(0.03f, 0.04f, 0.06f, 0.82f);

    static TMP_FontAsset fontBlack, fontBold, fontMedium;
    static Sprite gradient;

    [MenuItem("The Last Protocol/2 - Build Menu UI")]
    static void Build()
    {
        if (SceneManager.GetActiveScene().path != ScenePath)
        {
            EditorUtility.DisplayDialog("Open MainMenu first", "Open the scene Assets/Scenes 1/MainMenu.unity, then run this again.", "OK");
            return;
        }

        foreach (string n in new[] { "MenuCanvas", "EventSystem", "MenuManager" })
        {
            GameObject old = GameObject.Find(n);
            while (old != null) { Object.DestroyImmediate(old); old = GameObject.Find(n); }
        }

        fontBlack = MakeFont("Orbitron-Black.ttf", "Orbitron Black SDF");
        fontBold = MakeFont("Orbitron-Bold.ttf", "Orbitron Bold SDF");
        fontMedium = MakeFont("Orbitron-Medium.ttf", "Orbitron Medium SDF");
        gradient = MakeGradientSprite();

        // ------------------------------------------------ canvas + event system
        Camera cam = Camera.main;
        GameObject cg = new GameObject("MenuCanvas", typeof(RectTransform), typeof(Canvas), typeof(CanvasScaler), typeof(GraphicRaycaster));
        Canvas canvas = cg.GetComponent<Canvas>();
        canvas.renderMode = RenderMode.ScreenSpaceCamera;
        canvas.worldCamera = cam;
        canvas.planeDistance = 1f;
        CanvasScaler scaler = cg.GetComponent<CanvasScaler>();
        scaler.uiScaleMode = CanvasScaler.ScaleMode.ScaleWithScreenSize;
        scaler.referenceResolution = new Vector2(1920, 1080);
        scaler.matchWidthOrHeight = 0.5f;
        RectTransform root = (RectTransform)cg.transform;

        GameObject es = new GameObject("EventSystem", typeof(EventSystem));
        InputSystemUIInputModule module = es.AddComponent<InputSystemUIInputModule>();
        module.AssignDefaultActions();

        // ------------------------------------------------ left shade, title
        RectTransform shade = Rect("LeftShade", root);
        Anchor(shade, 0f, 0f, 0f, 1f, new Vector2(0f, 0.5f));
        shade.sizeDelta = new Vector2(1100f, 0f);
        shade.anchoredPosition = Vector2.zero;
        Image shadeImg = shade.gameObject.AddComponent<Image>();
        shadeImg.sprite = gradient; shadeImg.color = new Color(0f, 0f, 0f, 0.88f); shadeImg.raycastTarget = false;

        RectTransform titleGroup = Rect("TitleGroup", root);
        Stretch(titleGroup);
        TMP_Text title = Label("Title", titleGroup, "THE LAST PROTOCOL", fontBlack, 104, new Vector2(0, 450), new Vector2(1700, 140), Color.white, 14f);
        title.enableVertexGradient = true;
        title.colorGradient = new VertexGradient(new Color(1f, 0.97f, 0.9f), new Color(1f, 0.97f, 0.9f), new Color(1f, 0.55f, 0.12f), new Color(1f, 0.55f, 0.12f));
        TMP_Text sub = Label("Subtitle", titleGroup, "A SYSTEM FAILURE. ONE WAY OUT.", fontMedium, 30, new Vector2(0, 372), new Vector2(1400, 50), Amber, 16f);
        Image rule = Image("TitleRule", titleGroup, new Color(1f, 0.5f, 0.1f, 0.6f), new Vector2(0, 335), new Vector2(900, 2));
        rule.raycastTarget = false;

        // ------------------------------------------------ main buttons
        CanvasGroup mainGroup = Group("MainGroup", root);
        MenuButtonSet buttons = new MenuButtonSet();
        float x = -600f;
        buttons.enter = MainButton(mainGroup.transform, "EnterButton", "ENTER THE FACILITY", new Vector2(x, 60), true);
        buttons.howTo = MainButton(mainGroup.transform, "HowToButton", "HOW TO PLAY", new Vector2(x, -35), false);
        buttons.settings = MainButton(mainGroup.transform, "SettingsButton", "SETTINGS", new Vector2(x, -130), false);
        buttons.exit = MainButton(mainGroup.transform, "ExitButton", "EXIT GAME", new Vector2(x, -225), false);

        TMP_Text footer = Label("Footer", mainGroup.transform, "SOFTWARE TESTING SIMULATION  //  GRADUATION PROJECT  //  BUILD 0.1", fontMedium, 16, Vector2.zero, new Vector2(1100, 30), Dim, 4f);
        Anchor((RectTransform)footer.transform, 0f, 0f, 0f, 0f, new Vector2(0f, 0f));
        ((RectTransform)footer.transform).anchoredPosition = new Vector2(70, 40);
        footer.alignment = TextAlignmentOptions.Left;

        // ------------------------------------------------ How to play panel
        CanvasGroup howGroup = Group("HowToPlayGroup", root);
        RectTransform how = Panel("HowToPlayPanel", howGroup.transform, new Vector2(1240, 740), new Vector2(0, -40));
        Label("Heading", how, "HOW TO PLAY", fontBlack, 48, new Vector2(0, 310), new Vector2(1100, 70), Amber, 8f);
        Image("Rule", how, new Color(1f, 0.5f, 0.1f, 0.5f), new Vector2(0, 270), new Vector2(1060, 2));

        TMP_Text objective = Label("Objective", how, "OBJECTIVE\nThe facility's testing systems have failed. Find the hidden bugs in each room, solve the test puzzles and unlock the exit before the timer runs out.", fontMedium, 24, new Vector2(0, 175), new Vector2(1060, 160), Pale, 0f);
        objective.alignment = TextAlignmentOptions.TopLeft; objective.textWrappingMode = TextWrappingModes.Normal; objective.lineSpacing = 12f;

        TMP_Text controls = Label("Controls", how, "CONTROLS\n<color=#FF9424>VR</color>   Thumbstick: move     Trigger: select / press     Grip: grab\n<color=#FF9424>PC</color>   W A S D: move     Mouse: look     Left click: select     Esc: back", fontMedium, 24, new Vector2(0, 20), new Vector2(1060, 190), Pale, 0f);
        controls.alignment = TextAlignmentOptions.TopLeft; controls.textWrappingMode = TextWrappingModes.Normal; controls.lineSpacing = 16f;

        TMP_Text tips = Label("Tips", how, "TIPS\nExamine everything. A wrong answer teaches you something. Hints are available if you get stuck.", fontMedium, 24, new Vector2(0, -150), new Vector2(1060, 130), Pale, 0f);
        tips.alignment = TextAlignmentOptions.TopLeft; tips.textWrappingMode = TextWrappingModes.Normal; tips.lineSpacing = 12f;

        Button howBack = PanelButton("BackButton", how, "BACK", new Vector2(0, -310), new Vector2(300, 64));

        // ------------------------------------------------ Settings panel
        CanvasGroup setGroup = Group("SettingsGroup", root);
        RectTransform set = Panel("SettingsPanel", setGroup.transform, new Vector2(1040, 640), new Vector2(0, -40));
        Label("Heading", set, "SETTINGS", fontBlack, 48, new Vector2(0, 250), new Vector2(900, 70), Amber, 8f);
        Image("Rule", set, new Color(1f, 0.5f, 0.1f, 0.5f), new Vector2(0, 210), new Vector2(880, 2));

        Label("VolumeLabel", set, "MASTER VOLUME", fontBold, 22, new Vector2(-300, 120), new Vector2(380, 40), Pale, 3f).alignment = TextAlignmentOptions.Left;
        Slider volume = MakeSlider("VolumeSlider", set, new Vector2(90, 120), new Vector2(360, 36));
        TMP_Text volumeValue = Label("VolumeValue", set, "80%", fontMedium, 22, new Vector2(330, 120), new Vector2(120, 40), Amber, 0f);

        Label("QualityLabel", set, "GRAPHICS QUALITY", fontBold, 22, new Vector2(-300, 20), new Vector2(380, 40), Pale, 3f).alignment = TextAlignmentOptions.Left;
        Button qPrev = PanelButton("QualityPrev", set, "<", new Vector2(10, 20), new Vector2(60, 52));
        TMP_Text qValue = Label("QualityValue", set, "HIGH", fontMedium, 22, new Vector2(155, 20), new Vector2(180, 40), Amber, 2f);
        Button qNext = PanelButton("QualityNext", set, ">", new Vector2(300, 20), new Vector2(60, 52));

        Label("ScreenLabel", set, "DISPLAY MODE", fontBold, 22, new Vector2(-300, -80), new Vector2(380, 40), Pale, 3f).alignment = TextAlignmentOptions.Left;
        Button fsButton = PanelButton("FullscreenButton", set, "FULLSCREEN", new Vector2(155, -80), new Vector2(350, 52));
        TMP_Text fsValue = fsButton.GetComponentInChildren<TMP_Text>();

        Label("Note", set, "Settings are saved automatically.", fontMedium, 16, new Vector2(0, -170), new Vector2(800, 30), Dim, 2f);
        Button setBack = PanelButton("BackButton", set, "BACK", new Vector2(0, -250), new Vector2(300, 64));

        SettingsPanel sp = set.gameObject.AddComponent<SettingsPanel>();
        SerializedObject sps = new SerializedObject(sp);
        Ref(sps, "volumeSlider", volume); Ref(sps, "volumeValue", volumeValue);
        Ref(sps, "qualityPrev", qPrev); Ref(sps, "qualityNext", qNext); Ref(sps, "qualityValue", qValue);
        Ref(sps, "fullscreenButton", fsButton); Ref(sps, "fullscreenValue", fsValue);
        sps.ApplyModifiedProperties();

        // ------------------------------------------------ toast message + manager
        TMP_Text toast = Label("Toast", root, "", fontMedium, 22, new Vector2(0, -470), new Vector2(1500, 40), Amber, 2f);

        GameObject mg = new GameObject("MenuManager");
        AudioSource src = mg.AddComponent<AudioSource>();
        src.playOnAwake = false; src.spatialBlend = 0f;
        MenuManager mm = mg.AddComponent<MenuManager>();
        SerializedObject mso = new SerializedObject(mm);
        Ref(mso, "mainGroup", mainGroup); Ref(mso, "howToGroup", howGroup); Ref(mso, "settingsGroup", setGroup);
        Ref(mso, "enterButton", buttons.enter); Ref(mso, "howToButton", buttons.howTo);
        Ref(mso, "settingsButton", buttons.settings); Ref(mso, "exitButton", buttons.exit);
        Ref(mso, "howToBack", howBack); Ref(mso, "settingsBack", setBack);
        Ref(mso, "toastText", toast); Ref(mso, "audioSource", src);
        AudioClip click = FindClip("sfx_keypadClick");
        Ref(mso, "hoverClip", click); Ref(mso, "clickClip", click);
        mso.ApplyModifiedProperties();
        if (click == null) Debug.LogWarning("Keypad click sound not found; the menu will be silent until you assign clips on MenuManager.");

        EditorSceneManager.MarkSceneDirty(SceneManager.GetActiveScene());
        EditorSceneManager.SaveScene(SceneManager.GetActiveScene());
        Debug.Log("Menu UI built and the scene was saved. Press Play and use the buttons.");
    }

    class MenuButtonSet { public Button enter, howTo, settings, exit; }

    // ------------------------------------------------------------------ main button

    static Button MainButton(Transform parent, string name, string text, Vector2 pos, bool primary)
    {
        RectTransform b = Rect(name, parent);
        b.anchoredPosition = pos; b.sizeDelta = new Vector2(560, 76);
        Image bg = b.gameObject.AddComponent<Image>();
        bg.color = new Color(0.04f, 0.05f, 0.07f, 0.72f);
        Button btn = b.gameObject.AddComponent<Button>();
        btn.targetGraphic = bg;
        btn.transition = Selectable.Transition.None;

        // amber sweep that grows on hover (left aligned)
        RectTransform fill = Rect("HoverFill", b);
        Anchor(fill, 0f, 0.5f, 0f, 0.5f, new Vector2(0f, 0.5f));
        fill.anchoredPosition = Vector2.zero; fill.sizeDelta = new Vector2(0, 76);
        Image fi = fill.gameObject.AddComponent<Image>();
        fi.color = new Color(1f, 0.5f, 0.1f, primary ? 0.32f : 0.22f); fi.raycastTarget = false;

        RectTransform bar = Rect("AccentBar", b);
        Anchor(bar, 0f, 0.5f, 0f, 0.5f, new Vector2(0f, 0.5f));
        bar.anchoredPosition = Vector2.zero; bar.sizeDelta = new Vector2(6, 76);
        Image bi = bar.gameObject.AddComponent<Image>(); bi.raycastTarget = false;

        // thin bottom line
        Image line = Image("Line", b, new Color(1f, 1f, 1f, 0.08f), new Vector2(0, -37), new Vector2(560, 1));
        line.raycastTarget = false;

        TMP_Text label = Label("Label", b, text, primary ? fontBold : fontMedium, primary ? 29 : 27, new Vector2(30, 0), new Vector2(480, 60), Pale, 5f);
        label.alignment = TextAlignmentOptions.Left;
        TMP_Text chev = Label("Chevron", b, ">", fontBold, 30, new Vector2(240, 0), new Vector2(50, 60), Amber, 0f);
        chev.color = new Color(Amber.r, Amber.g, Amber.b, 0f);

        MenuButton mb = b.gameObject.AddComponent<MenuButton>();
        mb.hoverFill = fill; mb.accentBar = bi; mb.label = label; mb.chevron = chev;
        if (primary) mb.normalBar = new Color(1f, 0.55f, 0.12f, 1f);
        return btn;
    }

    // smaller button used inside the panels
    static Button PanelButton(string name, Transform parent, string text, Vector2 pos, Vector2 size)
    {
        RectTransform b = Rect(name, parent);
        b.anchoredPosition = pos; b.sizeDelta = size;
        Image bg = b.gameObject.AddComponent<Image>();
        bg.color = new Color(0.1f, 0.08f, 0.06f, 0.9f);
        Button btn = b.gameObject.AddComponent<Button>();
        btn.targetGraphic = bg;
        ColorBlock cb = btn.colors;
        cb.normalColor = Color.white; cb.highlightedColor = new Color(1f, 0.75f, 0.45f); cb.pressedColor = new Color(1f, 0.55f, 0.15f);
        cb.selectedColor = cb.highlightedColor; cb.fadeDuration = 0.12f;
        btn.colors = cb;
        Corners(b, Amber, 14f, 2f);
        Label("Text", b, text, fontBold, 24, Vector2.zero, size, Pale, 3f);
        return btn;
    }

    static RectTransform Panel(string name, Transform parent, Vector2 size, Vector2 pos)
    {
        RectTransform p = Rect(name, parent);
        p.anchoredPosition = pos; p.sizeDelta = size;
        Image bg = p.gameObject.AddComponent<Image>();
        bg.color = Glass; bg.raycastTarget = true;
        Corners(p, Amber, 34f, 3f);
        return p;
    }

    // four amber corner brackets
    static void Corners(RectTransform parent, Color c, float len, float thick)
    {
        for (int sx = -1; sx <= 1; sx += 2)
            for (int sy = -1; sy <= 1; sy += 2)
            {
                Vector2 half = parent.sizeDelta * 0.5f;
                Vector2 corner = new Vector2(sx * half.x, sy * half.y);
                Image h = Image("CornerH", parent, c, corner + new Vector2(-sx * len * 0.5f + sx * 0f, 0f), new Vector2(len, thick));
                Image v = Image("CornerV", parent, c, corner + new Vector2(0f, -sy * len * 0.5f), new Vector2(thick, len));
                h.raycastTarget = false; v.raycastTarget = false;
            }
    }

    static Slider MakeSlider(string name, Transform parent, Vector2 pos, Vector2 size)
    {
        RectTransform root = Rect(name, parent);
        root.anchoredPosition = pos; root.sizeDelta = size;
        Slider s = root.gameObject.AddComponent<Slider>();

        RectTransform bg = Rect("Background", root);
        Anchor(bg, 0f, 0.5f, 1f, 0.5f, new Vector2(0.5f, 0.5f)); bg.sizeDelta = new Vector2(0, 10); bg.anchoredPosition = Vector2.zero;
        bg.gameObject.AddComponent<Image>().color = new Color(1f, 1f, 1f, 0.15f);

        RectTransform fillArea = Rect("Fill Area", root);
        Anchor(fillArea, 0f, 0.5f, 1f, 0.5f, new Vector2(0.5f, 0.5f)); fillArea.sizeDelta = new Vector2(-10, 10); fillArea.anchoredPosition = Vector2.zero;
        RectTransform fill = Rect("Fill", fillArea);
        fill.sizeDelta = new Vector2(10, 0);
        fill.gameObject.AddComponent<Image>().color = Amber;

        RectTransform handleArea = Rect("Handle Slide Area", root);
        Stretch(handleArea); handleArea.offsetMin = new Vector2(10, 0); handleArea.offsetMax = new Vector2(-10, 0);
        RectTransform handle = Rect("Handle", handleArea);
        handle.sizeDelta = new Vector2(18, 38);
        Image hi = handle.gameObject.AddComponent<Image>(); hi.color = Color.white;

        s.fillRect = fill; s.handleRect = handle; s.targetGraphic = hi;
        s.direction = Slider.Direction.LeftToRight; s.minValue = 0f; s.maxValue = 1f; s.value = 0.8f;
        return s;
    }

    // ------------------------------------------------------------------ element helpers

    static RectTransform Rect(string name, Transform parent)
    {
        GameObject go = new GameObject(name, typeof(RectTransform));
        go.transform.SetParent(parent, false);
        RectTransform rt = (RectTransform)go.transform;
        rt.anchorMin = rt.anchorMax = new Vector2(0.5f, 0.5f);
        rt.pivot = new Vector2(0.5f, 0.5f);
        return rt;
    }

    static void Stretch(RectTransform rt)
    {
        rt.anchorMin = Vector2.zero; rt.anchorMax = Vector2.one; rt.offsetMin = Vector2.zero; rt.offsetMax = Vector2.zero;
    }

    static void Anchor(RectTransform rt, float minX, float minY, float maxX, float maxY, Vector2 pivot)
    {
        rt.anchorMin = new Vector2(minX, minY); rt.anchorMax = new Vector2(maxX, maxY); rt.pivot = pivot;
    }

    static CanvasGroup Group(string name, Transform parent)
    {
        RectTransform rt = Rect(name, parent);
        Stretch(rt);
        return rt.gameObject.AddComponent<CanvasGroup>();
    }

    static Image Image(string name, Transform parent, Color color, Vector2 pos, Vector2 size)
    {
        RectTransform rt = Rect(name, parent);
        rt.anchoredPosition = pos; rt.sizeDelta = size;
        Image i = rt.gameObject.AddComponent<Image>();
        i.color = color;
        return i;
    }

    static TMP_Text Label(string name, Transform parent, string text, TMP_FontAsset font, float size, Vector2 pos, Vector2 box, Color color, float spacing)
    {
        RectTransform rt = Rect(name, parent);
        rt.anchoredPosition = pos; rt.sizeDelta = box;
        TextMeshProUGUI t = rt.gameObject.AddComponent<TextMeshProUGUI>();
        if (font != null) t.font = font;
        t.text = text; t.fontSize = size; t.color = color; t.characterSpacing = spacing;
        t.alignment = TextAlignmentOptions.Center;
        t.textWrappingMode = TextWrappingModes.NoWrap;
        t.raycastTarget = false;
        return t;
    }

    static void Ref(SerializedObject so, string field, Object value)
    {
        SerializedProperty p = so.FindProperty(field);
        if (p == null) { Debug.LogWarning("Missing field: " + field); return; }
        p.objectReferenceValue = value;
    }

    // ------------------------------------------------------------------ assets

    static TMP_FontAsset MakeFont(string file, string assetName)
    {
        string path = Generated + "/" + assetName + ".asset";
        TMP_FontAsset existing = AssetDatabase.LoadAssetAtPath<TMP_FontAsset>(path);
        if (existing != null) return existing;
        try
        {
            Font font = AssetDatabase.LoadAssetAtPath<Font>(FontFolder + file);
            if (font == null) { Debug.LogWarning("Font not found: " + FontFolder + file); return null; }
            TMP_FontAsset fa = TMP_FontAsset.CreateFontAsset(font, 90, 9, UnityEngine.TextCore.LowLevel.GlyphRenderMode.SDFAA, 1024, 1024, AtlasPopulationMode.Dynamic, true);
            fa.name = assetName;
            AssetDatabase.CreateAsset(fa, path);
            fa.material.name = assetName + " Material";
            AssetDatabase.AddObjectToAsset(fa.material, fa);
            if (fa.atlasTextures != null && fa.atlasTextures.Length > 0)
            {
                fa.atlasTextures[0].name = assetName + " Atlas";
                AssetDatabase.AddObjectToAsset(fa.atlasTextures[0], fa);
            }
            AssetDatabase.SaveAssets();
            return fa;
        }
        catch (System.Exception ex)
        {
            Debug.LogWarning("Could not create font asset " + assetName + " (the default font is used): " + ex.Message);
            return null;
        }
    }

    static Sprite MakeGradientSprite()
    {
        string p = Generated + "/UIGradient.png";
        if (!File.Exists(p))
        {
            int w = 256, h = 4;
            Texture2D t = new Texture2D(w, h, TextureFormat.RGBA32, false);
            for (int x = 0; x < w; x++)
            {
                float a = Mathf.Pow(1f - (float)x / (w - 1), 1.6f);
                for (int y = 0; y < h; y++) t.SetPixel(x, y, new Color(1, 1, 1, a));
            }
            File.WriteAllBytes(p, t.EncodeToPNG());
            Object.DestroyImmediate(t);
            AssetDatabase.ImportAsset(p, ImportAssetOptions.ForceUpdate);
        }
        TextureImporter imp = AssetImporter.GetAtPath(p) as TextureImporter;
        if (imp != null && imp.textureType != TextureImporterType.Sprite)
        {
            imp.textureType = TextureImporterType.Sprite;
            imp.alphaIsTransparency = true; imp.mipmapEnabled = false; imp.wrapMode = TextureWrapMode.Clamp;
            imp.SaveAndReimport();
        }
        return AssetDatabase.LoadAssetAtPath<Sprite>(p);
    }

    static AudioClip FindClip(string name)
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
