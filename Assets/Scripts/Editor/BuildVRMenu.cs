#if UNITY_EDITOR
using System.IO;
using UnityEditor;
using UnityEditor.SceneManagement;
using UnityEngine;
using UnityEngine.EventSystems;
using UnityEngine.InputSystem.UI;
using UnityEngine.Rendering;
using UnityEngine.Rendering.Universal;
using UnityEngine.SceneManagement;
using UnityEngine.UI;
using UnityEngine.XR.Interaction.Toolkit.UI;

// Menu: The Last Protocol > 5 - Make Menu VR
// STAGE 6: turns the flat menu into a VR menu.
//   - adds the XR Origin (headset camera + both controllers with pointer rays) and the XR Interaction Simulator
//   - removes the flat camera and switches the menu to a panel floating in front of the player
//   - lets the controllers click it, and connects the vault sequence to the XR Origin
// Run it once, with the MainMenu scene open, after steps 1, 2 and 3.
public static class BuildVRMenu
{
    const string MenuScenePath = "Assets/Scenes 1/MainMenu.unity";
    static readonly Vector3 RigStart = new Vector3(0f, 0f, -5.5f);
    const float MenuDistance = 1.5f;     // metres in front of the player (arm's reach is about 0.7 m)
    const float MenuHeight = 1.5f;       // centre of the panel (about chest / eye level)
    const float MenuScale = 0.0014f;     // 1920 px = 2.7 m wide

    [MenuItem("The Last Protocol/5 - Make Menu VR")]
    static void Build()
    {
        if (SceneManager.GetActiveScene().path != MenuScenePath)
        {
            EditorUtility.DisplayDialog("Open MainMenu first", "Open Assets/Scenes 1/MainMenu.unity, then run this again.", "OK");
            return;
        }
        GameObject canvasGo = GameObject.Find("MenuCanvas");
        GameObject vault = GameObject.Find("VaultDoor");
        if (canvasGo == null || vault == null)
        {
            EditorUtility.DisplayDialog("Missing parts", "Run steps 1, 2 and 3 first (environment, menu UI, vault sequence).", "OK");
            return;
        }

        // ---------------------------------------------------- the XR Origin
        GameObject rig = GameObject.Find("XR Origin Hands (XR Rig)");
        if (rig == null)
        {
            GameObject prefab = FindPrefab("XR Origin Hands (XR Rig)");
            if (prefab == null) { EditorUtility.DisplayDialog("XR Origin not found", "Could not find the prefab 'XR Origin Hands (XR Rig)' in Assets/Samples. Tell me and I will point to another rig.", "OK"); return; }
            rig = (GameObject)PrefabUtility.InstantiatePrefab(prefab);
            rig.name = "XR Origin Hands (XR Rig)";
        }
        rig.transform.position = RigStart;
        rig.transform.rotation = Quaternion.identity;

        // remove the flat camera made in step 1 (it is a root object called "Main Camera")
        foreach (GameObject go in SceneManager.GetActiveScene().GetRootGameObjects())
            if (go.name == "Main Camera") Object.DestroyImmediate(go);

        // keep the player in place: no walking or teleporting in the menu
        Transform loco = Deep(rig.transform, "Locomotion");
        if (loco != null) loco.gameObject.SetActive(false);

        // keyboard + mouse VR simulator (lets you test without a headset)
        if (GameObject.Find("XR Interaction Simulator") == null)
        {
            GameObject simPrefab = FindPrefab("XR Interaction Simulator");
            if (simPrefab != null) PrefabUtility.InstantiatePrefab(simPrefab);
            else Debug.LogWarning("XR Interaction Simulator prefab not found. Without a headset you will not be able to move the pointer; copy it from your Museum_Home scene.");
        }

        Camera cam = rig.GetComponentInChildren<Camera>(true);
        if (cam != null)
        {
            cam.clearFlags = CameraClearFlags.SolidColor;
            cam.backgroundColor = Color.black;
            cam.nearClipPlane = 0.05f; cam.farClipPlane = 60f;
            UniversalAdditionalCameraData data = cam.GetComponent<UniversalAdditionalCameraData>();
            if (data == null) data = cam.gameObject.AddComponent<UniversalAdditionalCameraData>();
            data.renderPostProcessing = true;
        }
        else Debug.LogWarning("No camera found inside the XR Origin.");

        // ---------------------------------------------------- the menu becomes a floating panel
        Canvas canvas = canvasGo.GetComponent<Canvas>();
        canvas.renderMode = RenderMode.WorldSpace;
        canvas.worldCamera = cam;
        CanvasScaler scaler = canvasGo.GetComponent<CanvasScaler>();
        if (scaler != null) scaler.uiScaleMode = CanvasScaler.ScaleMode.ConstantPixelSize;
        RectTransform rt = (RectTransform)canvasGo.transform;
        rt.sizeDelta = new Vector2(1920, 1080);
        rt.localScale = Vector3.one * MenuScale;
        rt.position = new Vector3(0f, MenuHeight, RigStart.z + MenuDistance);
        rt.rotation = Quaternion.identity;
        if (canvasGo.GetComponent<TrackedDeviceGraphicRaycaster>() == null) canvasGo.AddComponent<TrackedDeviceGraphicRaycaster>();

        // the event system must understand XR controllers (it still accepts the mouse)
        GameObject es = GameObject.Find("EventSystem");
        if (es == null) es = new GameObject("EventSystem", typeof(EventSystem));
        InputSystemUIInputModule flat = es.GetComponent<InputSystemUIInputModule>();
        if (flat != null) Object.DestroyImmediate(flat);
        if (es.GetComponent<XRUIInputModule>() == null) es.AddComponent<XRUIInputModule>();

        // ---------------------------------------------------- keep it player -> menu -> vault
        GameObject old2 = GameObject.Find("MenuPlacement");
        if (old2 != null) Object.DestroyImmediate(old2);
        GameObject placeGo = new GameObject("MenuPlacement");
        MenuPlacement place = placeGo.AddComponent<MenuPlacement>();
        SerializedObject pso = new SerializedObject(place);
        Ref(pso, "rig", rig.transform);
        Ref(pso, "canvas", canvasGo.transform);
        Ref(pso, "vault", vault.transform);
        pso.FindProperty("distance").floatValue = MenuDistance;
        pso.ApplyModifiedProperties();

        // ---------------------------------------------------- VR fade (a black quad in front of the eyes)
        Renderer fadeQuad = null;
        if (cam != null)
        {
            Transform old = cam.transform.Find("VRFade");
            if (old != null) Object.DestroyImmediate(old.gameObject);
            GameObject q = GameObject.CreatePrimitive(PrimitiveType.Quad);
            q.name = "VRFade";
            Object.DestroyImmediate(q.GetComponent<Collider>());
            q.transform.SetParent(cam.transform, false);
            q.transform.localPosition = new Vector3(0, 0, 0.3f);
            q.transform.localScale = new Vector3(2.5f, 2.5f, 1f);
            fadeQuad = q.GetComponent<Renderer>();
            fadeQuad.sharedMaterial = FadeMaterial();
            fadeQuad.shadowCastingMode = ShadowCastingMode.Off;
            fadeQuad.enabled = false;
        }

        // ---------------------------------------------------- connect the vault sequence
        VaultSequence seq = vault.GetComponent<VaultSequence>();
        if (seq != null)
        {
            SerializedObject so = new SerializedObject(seq);
            Ref(so, "rigRoot", rig.transform);
            Ref(so, "fadeQuad", fadeQuad);
            Ref(so, "cam", cam);
            Ref(so, "cameraSway", null);
            Ref(so, "fade", null);
            so.ApplyModifiedProperties();
        }
        else Debug.LogWarning("VaultSequence not found on VaultDoor. Run '3 - Build Vault Sequence', then this step again.");

        EditorSceneManager.MarkSceneDirty(SceneManager.GetActiveScene());
        EditorSceneManager.SaveScene(SceneManager.GetActiveScene());
        Debug.Log("Menu is now VR: XR Origin added, menu floats in front of you, controllers can click it. Press Play.");
    }

    static Material FadeMaterial()
    {
        string path = "Assets/MainMenu/Materials/M_VRFade.mat";
        Material m = AssetDatabase.LoadAssetAtPath<Material>(path);
        if (m == null)
        {
            m = new Material(Shader.Find("Universal Render Pipeline/Unlit"));
            AssetDatabase.CreateAsset(m, path);
        }
        m.SetFloat("_Surface", 1f); m.SetFloat("_Blend", 0f);
        m.SetFloat("_SrcBlend", (float)BlendMode.SrcAlpha); m.SetFloat("_DstBlend", (float)BlendMode.OneMinusSrcAlpha);
        m.SetFloat("_SrcBlendAlpha", (float)BlendMode.One); m.SetFloat("_DstBlendAlpha", (float)BlendMode.OneMinusSrcAlpha);
        m.SetFloat("_ZWrite", 0f);
        m.EnableKeyword("_SURFACE_TYPE_TRANSPARENT");
        m.SetOverrideTag("RenderType", "Transparent");
        m.renderQueue = 4000;
        m.SetColor("_BaseColor", new Color(0, 0, 0, 0));
        EditorUtility.SetDirty(m);
        return m;
    }

    static Transform Deep(Transform root, string name)
    {
        foreach (Transform c in root)
        {
            if (c.name == name) return c;
            Transform r = Deep(c, name);
            if (r != null) return r;
        }
        return null;
    }

    static GameObject FindPrefab(string name)
    {
        string fallback = null;
        foreach (string guid in AssetDatabase.FindAssets(name + " t:Prefab"))
        {
            string p = AssetDatabase.GUIDToAssetPath(guid);
            if (Path.GetFileNameWithoutExtension(p) != name) continue;
            if (p.StartsWith("Assets/Samples/")) return AssetDatabase.LoadAssetAtPath<GameObject>(p);
            if (fallback == null) fallback = p;
        }
        return fallback != null ? AssetDatabase.LoadAssetAtPath<GameObject>(fallback) : null;
    }

    static void Ref(SerializedObject so, string field, Object value)
    {
        SerializedProperty p = so.FindProperty(field);
        if (p == null) { Debug.LogWarning("Missing field: " + field); return; }
        p.objectReferenceValue = value;
    }
}
#endif
