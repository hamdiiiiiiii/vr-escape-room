#if UNITY_EDITOR
using System.IO;
using UnityEditor;
using UnityEditor.SceneManagement;
using UnityEngine;
using UnityEngine.Rendering;
using UnityEngine.SceneManagement;

// Menu: The Last Protocol > 6 - Add Atmosphere (sound + light beams)
// STAGE 7: a looping industrial hum (made in code, no download needed) and visible light beams
// from the warning beacon and the ceiling lamps. Safe to run again.
public static class BuildAtmosphere
{
    const string MenuScenePath = "Assets/Scenes 1/MainMenu.unity";
    const string Generated = "Assets/MainMenu/Generated";

    [MenuItem("The Last Protocol/6 - Add Atmosphere (sound + light beams)")]
    static void Build()
    {
        if (SceneManager.GetActiveScene().path != MenuScenePath)
        {
            EditorUtility.DisplayDialog("Open MainMenu first", "Open Assets/Scenes 1/MainMenu.unity, then run this again.", "OK");
            return;
        }

        AddHum();
        AddBeams();

        EditorSceneManager.MarkSceneDirty(SceneManager.GetActiveScene());
        EditorSceneManager.SaveScene(SceneManager.GetActiveScene());
        Debug.Log("Atmosphere added (hum + light beams) and scene saved. Press Play.");
    }

    // ---------------------------------------------------------------- sound

    static void AddHum()
    {
        if (!AssetDatabase.IsValidFolder(Generated + "/Audio")) AssetDatabase.CreateFolder(Generated, "Audio");
        string path = Generated + "/Audio/IndustrialHum.wav";
        if (!File.Exists(path))
        {
            const int rate = 44100;
            const int seconds = 8;                       // every partial has a whole number of cycles in 8 s, so the loop is seamless
            float[] data = new float[rate * seconds];
            System.Random rnd = new System.Random(5);
            int partials = 36;
            float[] freq = new float[partials], amp = new float[partials], phase = new float[partials];
            for (int i = 0; i < partials; i++)
            {
                freq[i] = (i < 4 ? 55f * (i + 1) : 120f + i * 37.5f) ;   // multiples of 1/8 Hz
                freq[i] = Mathf.Round(freq[i] * 8f) / 8f;
                amp[i] = i < 4 ? 0.22f / (i + 1) : 0.012f * (float)rnd.NextDouble();
                phase[i] = (float)rnd.NextDouble() * Mathf.PI * 2f;
            }
            for (int n = 0; n < data.Length; n++)
            {
                float t = (float)n / rate;
                float v = 0f;
                for (int i = 0; i < partials; i++) v += amp[i] * Mathf.Sin(2f * Mathf.PI * freq[i] * t + phase[i]);
                v *= 0.85f + 0.15f * Mathf.Sin(2f * Mathf.PI * 0.25f * t);         // slow swell
                data[n] = v;
            }
            WriteWav(path, data, rate);
            AssetDatabase.ImportAsset(path, ImportAssetOptions.ForceUpdate);
        }

        AudioClip clip = AssetDatabase.LoadAssetAtPath<AudioClip>(path);
        GameObject old = GameObject.Find("MenuAmbience");
        if (old != null) Object.DestroyImmediate(old);
        GameObject go = new GameObject("MenuAmbience");
        AudioSource src = go.AddComponent<AudioSource>();
        src.clip = clip; src.loop = true; src.playOnAwake = true; src.spatialBlend = 0f; src.volume = 0.35f;
    }

    static void WriteWav(string path, float[] samples, int rate)
    {
        using (FileStream fs = new FileStream(path, FileMode.Create))
        using (BinaryWriter w = new BinaryWriter(fs))
        {
            int bytes = samples.Length * 2;
            w.Write(new[] { 'R', 'I', 'F', 'F' }); w.Write(36 + bytes); w.Write(new[] { 'W', 'A', 'V', 'E' });
            w.Write(new[] { 'f', 'm', 't', ' ' }); w.Write(16); w.Write((short)1); w.Write((short)1);
            w.Write(rate); w.Write(rate * 2); w.Write((short)2); w.Write((short)16);
            w.Write(new[] { 'd', 'a', 't', 'a' }); w.Write(bytes);
            foreach (float s in samples) w.Write((short)(Mathf.Clamp(s, -1f, 1f) * 32000f));
        }
    }

    // ---------------------------------------------------------------- light beams

    static void AddBeams()
    {
        Material red = BeamMaterial("M_BeamRed", new Color(1f, 0.1f, 0.05f, 0.45f));
        Material cool = BeamMaterial("M_BeamLamp", new Color(0.6f, 0.8f, 1f, 0.10f));

        GameObject oldRoot = GameObject.Find("LightBeams");
        if (oldRoot != null) Object.DestroyImmediate(oldRoot);
        GameObject root = new GameObject("LightBeams");

        // sweeping beacon beam: child of the rotor so it spins with the spotlight
        MenuBeacon beacon = Object.FindFirstObjectByType<MenuBeacon>();
        if (beacon != null && beacon.rotor != null)
        {
            Transform oldCone = beacon.rotor.Find("BeaconBeam");
            if (oldCone != null) Object.DestroyImmediate(oldCone.gameObject);
            GameObject cone = NewCone("BeaconBeam", beacon.rotor, Cone("Cone_Beacon", 4.6f, 14f, 24), red);
            cone.transform.localPosition = Vector3.zero;
            cone.transform.localRotation = Quaternion.Euler(12f, 0f, 0f);       // same tilt as the spotlight
            SerializedObject so = new SerializedObject(beacon);
            SerializedProperty p = so.FindProperty("beamCone");
            if (p != null) { p.objectReferenceValue = cone.GetComponent<Renderer>(); so.ApplyModifiedProperties(); }
        }
        else Debug.LogWarning("Beacon not found; the red beam was skipped.");

        // soft shafts under the three ceiling lamps
        float[] zs = { -6f, -1.5f, 3f };
        foreach (float z in zs)
        {
            GameObject shaft = NewCone("LampShaft", root.transform, Cone("Cone_Lamp", 1.7f, 5.4f, 24), cool);
            shaft.transform.position = new Vector3(0f, 5.7f, z);
            shaft.transform.rotation = Quaternion.Euler(90f, 0f, 0f);           // points straight down
        }
    }

    static GameObject NewCone(string name, Transform parent, Mesh mesh, Material mat)
    {
        GameObject go = new GameObject(name);
        go.transform.SetParent(parent, false);
        go.AddComponent<MeshFilter>().sharedMesh = mesh;
        MeshRenderer r = go.AddComponent<MeshRenderer>();
        r.sharedMaterial = mat;
        r.shadowCastingMode = ShadowCastingMode.Off;
        r.receiveShadows = false;
        return go;
    }

    static Material BeamMaterial(string name, Color c)
    {
        string path = "Assets/MainMenu/Materials/" + name + ".mat";
        Material m = AssetDatabase.LoadAssetAtPath<Material>(path);
        Shader sh = Shader.Find("Universal Render Pipeline/Particles/Unlit");
        if (m == null) { m = new Material(sh); AssetDatabase.CreateAsset(m, path); } else m.shader = sh;
        m.SetFloat("_Surface", 1f); m.SetFloat("_Blend", 2f);
        m.SetFloat("_SrcBlend", (float)BlendMode.SrcAlpha); m.SetFloat("_DstBlend", (float)BlendMode.One);
        m.SetFloat("_SrcBlendAlpha", (float)BlendMode.One); m.SetFloat("_DstBlendAlpha", (float)BlendMode.One);
        m.SetFloat("_ZWrite", 0f);
        m.SetFloat("_Cull", 0f);                                  // visible from inside and outside
        m.EnableKeyword("_SURFACE_TYPE_TRANSPARENT");
        m.SetOverrideTag("RenderType", "Transparent");
        m.renderQueue = 3000;
        m.SetColor("_BaseColor", c);
        EditorUtility.SetDirty(m);
        return m;
    }

    // open cone: tip at the origin, widening along +Z; the colour fades out toward the wide end
    static Mesh Cone(string name, float radius, float length, int seg)
    {
        string path = Generated + "/" + name + ".asset";
        AssetDatabase.DeleteAsset(path);
        Vector3[] v = new Vector3[seg + 1];
        Color[] col = new Color[seg + 1];
        v[0] = Vector3.zero; col[0] = new Color(1, 1, 1, 1f);
        for (int i = 0; i < seg; i++)
        {
            float a = (float)i / seg * Mathf.PI * 2f;
            v[i + 1] = new Vector3(Mathf.Cos(a) * radius, Mathf.Sin(a) * radius, length);
            col[i + 1] = new Color(1, 1, 1, 0f);
        }
        int[] t = new int[seg * 3];
        for (int i = 0; i < seg; i++) { t[i * 3] = 0; t[i * 3 + 1] = 1 + (i + 1) % seg; t[i * 3 + 2] = 1 + i; }
        Mesh m = new Mesh { name = name, vertices = v, colors = col, triangles = t };
        m.RecalculateNormals(); m.RecalculateBounds();
        AssetDatabase.CreateAsset(m, path);
        return m;
    }
}
#endif
