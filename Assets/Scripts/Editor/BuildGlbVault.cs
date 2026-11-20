#if UNITY_EDITOR
using System.Collections.Generic;
using System.IO;
using System.Text;
using UnityEditor;
using UnityEditor.SceneManagement;
using UnityEngine;
using UnityEngine.SceneManagement;

// Menu: The Last Protocol > 7 - Use Downloaded Vault Door
// Takes the downloaded bank_vault GLB, cuts its merged meshes into separate pieces,
// works out which pieces are the door, and hooks the door up to VaultSequence so it swings open.
// The old coded vault is only DISABLED (never deleted). Safe to run again.
public static class BuildGlbVault
{
    const string GlbPath = "Assets/MainMenu/Imported/bank_vault__armored_door__low_poly.glb";
    const string Gen = "Assets/MainMenu/Generated";
    static readonly Vector3 VaultCenter = new Vector3(0f, 2.6f, 8.2f);
    const float TargetSize = 5.2f;

    class Piece { public Mesh mesh; public Material mat; public Bounds b; public int tris; public string name; }

    [MenuItem("The Last Protocol/7 - Use Downloaded Vault Door")]
    static void Build()
    {
        if (SceneManager.GetActiveScene().path != "Assets/Scenes 1/MainMenu.unity")
        { EditorUtility.DisplayDialog("Open MainMenu first", "Open Assets/Scenes 1/MainMenu.unity and run again.", "OK"); return; }

        GameObject prefab = AssetDatabase.LoadAssetAtPath<GameObject>(GlbPath);
        if (prefab == null) { Debug.LogError("GLB not found at " + GlbPath); return; }

        // 1. gather all triangles of the model in model space and split them into connected islands
        GameObject temp = (GameObject)PrefabUtility.InstantiatePrefab(prefab);
        List<Piece> pieces = new List<Piece>();
        Bounds all = new Bounds(); bool first = true;
        List<(Mesh, Matrix4x4, Material)> raw = new List<(Mesh, Matrix4x4, Material)>();
        foreach (MeshFilter mf in temp.GetComponentsInChildren<MeshFilter>())
        {
            MeshRenderer mr = mf.GetComponent<MeshRenderer>();
            if (mr == null || mf.sharedMesh == null) continue;
            Matrix4x4 m = temp.transform.worldToLocalMatrix * mf.transform.localToWorldMatrix;
            for (int s = 0; s < mf.sharedMesh.subMeshCount; s++)
            {
                Material mat = s < mr.sharedMaterials.Length ? mr.sharedMaterials[s] : mr.sharedMaterial;
                raw.Add((mf.sharedMesh, m * Matrix4x4.identity, mat));
            }
        }
        // model bounds
        foreach (var r in raw)
            foreach (Vector3 v in r.Item1.vertices)
            { Vector3 p = r.Item2.MultiplyPoint3x4(v); if (first) { all = new Bounds(p, Vector3.zero); first = false; } else all.Encapsulate(p); }
        Object.DestroyImmediate(temp);
        if (first) { Debug.LogError("The GLB has no meshes."); return; }

        // aligned space: depth (thinnest horizontal axis) becomes Z, centred, scaled
        Vector3 sz = all.size;
        bool depthIsX = sz.x < sz.z;
        float lateral = depthIsX ? sz.z : sz.x;
        float scale = TargetSize / Mathf.Max(lateral, sz.y);
        Quaternion rot = depthIsX ? Quaternion.Euler(0f, 90f, 0f) : Quaternion.identity;
        Matrix4x4 align = Matrix4x4.Scale(Vector3.one * scale) * Matrix4x4.Rotate(rot) * Matrix4x4.Translate(-all.center);

        if (!AssetDatabase.IsValidFolder(Gen + "/Vault")) AssetDatabase.CreateFolder(Gen, "Vault");
        AssetDatabase.DeleteAsset(Gen + "/Vault"); AssetDatabase.CreateFolder(Gen, "Vault");

        int count = 0; int ri = 0;
        foreach (var r in raw)
        {
            Mesh src = r.Item1; Matrix4x4 m = align * r.Item2;
            Vector3[] vs = src.vertices; Vector3[] ns = src.normals; Vector2[] uv = src.uv;
            // which submesh? find by matching material order again
            int sub = 0; { int idx = 0; foreach (var q in raw) { if (q.Item1 == src && q.Item3 == r.Item3) { sub = idx; break; } if (q.Item1 == src) idx++; } }
            int[] tris = src.GetTriangles(Mathf.Min(sub, src.subMeshCount - 1));
            // union-find on welded positions
            Dictionary<Vector3Int, int> key = new Dictionary<Vector3Int, int>();
            int[] vk = new int[vs.Length];
            for (int i = 0; i < vs.Length; i++)
            {
                Vector3Int k = Vector3Int.RoundToInt(vs[i] * 500f);
                if (!key.TryGetValue(k, out int id)) { id = key.Count; key[k] = id; }
                vk[i] = id;
            }
            int[] parent = new int[key.Count]; for (int i = 0; i < parent.Length; i++) parent[i] = i;
            System.Func<int, int> find = null; find = x => { while (parent[x] != x) { parent[x] = parent[parent[x]]; x = parent[x]; } return x; };
            for (int t = 0; t < tris.Length; t += 3)
            { int a = find(vk[tris[t]]), b = find(vk[tris[t + 1]]), c = find(vk[tris[t + 2]]); parent[b] = a; parent[c] = a; }
            Dictionary<int, List<int>> groups = new Dictionary<int, List<int>>();
            for (int t = 0; t < tris.Length; t += 3)
            { int g = find(vk[tris[t]]); if (!groups.TryGetValue(g, out var l)) { l = new List<int>(); groups[g] = l; } l.Add(tris[t]); l.Add(tris[t + 1]); l.Add(tris[t + 2]); }

            foreach (var kv in groups)
            {
                if (kv.Value.Count < 3 * 4) continue;                       // ignore tiny crumbs
                Dictionary<int, int> remap = new Dictionary<int, int>();
                List<Vector3> pv = new List<Vector3>(), pn = new List<Vector3>(); List<Vector2> pu = new List<Vector2>(); List<int> pt = new List<int>();
                foreach (int vi in kv.Value)
                {
                    if (!remap.TryGetValue(vi, out int ni))
                    {
                        ni = pv.Count; remap[vi] = ni;
                        pv.Add(m.MultiplyPoint3x4(vs[vi]));
                        pn.Add(ns.Length > vi ? m.MultiplyVector(ns[vi]).normalized : Vector3.up);
                        pu.Add(uv.Length > vi ? uv[vi] : Vector2.zero);
                    }
                    pt.Add(ni);
                }
                Mesh pm = new Mesh { name = "VaultPiece_" + count };
                if (pv.Count > 65000) pm.indexFormat = UnityEngine.Rendering.IndexFormat.UInt32;
                pm.SetVertices(pv); pm.SetNormals(pn); pm.SetUVs(0, pu); pm.SetTriangles(pt, 0);
                pm.RecalculateBounds();
                AssetDatabase.CreateAsset(pm, Gen + "/Vault/VaultPiece_" + count + ".asset");
                pieces.Add(new Piece { mesh = pm, mat = r.Item3, b = pm.bounds, tris = pt.Count / 3, name = "Piece_" + count });
                count++;
            }
            ri++;
        }
        if (pieces.Count == 0) { Debug.LogError("Could not split the model into pieces."); return; }

        // 2. classify: frame = biggest lateral island, door-core = biggest of the rest
        System.Func<Piece, float> area = p => p.b.size.x * p.b.size.y * p.b.size.z;
        Piece frame = pieces[0]; foreach (Piece p in pieces) if (area(p) > area(frame)) frame = p;
        Piece core = null; foreach (Piece p in pieces) if (p != frame && (core == null || area(p) > area(core))) core = p;
        HashSet<Piece> door = new HashSet<Piece>();
        if (core != null)
        {
            bool thinX = core.b.size.x < core.b.size.z;
            Bounds cb = core.b; cb.Expand(thinX ? new Vector3(0.45f, 0.1f, 0.1f) : new Vector3(0.1f, 0.1f, 0.45f));
            foreach (Piece p in pieces)
                if (p != frame && cb.Contains(p.b.center)) door.Add(p);
        }

        // report
        StringBuilder sb = new StringBuilder("VAULT PIECES (aligned, metres)\n");
        foreach (Piece p in pieces)
            sb.AppendLine(p.name + "  tris=" + p.tris + "  center=" + p.b.center.ToString("F2") + "  size=" + p.b.size.ToString("F2") + "  -> " + (p == frame ? "FRAME(main)" : door.Contains(p) ? "DOOR" : "frame"));
        File.WriteAllText(Gen + "/VaultPieces.txt", sb.ToString());
        Debug.Log(sb.ToString());

        // 3. build objects
        GameObject old = GameObject.Find("GlbVault"); if (old != null) Object.DestroyImmediate(old);
        GameObject root = new GameObject("GlbVault");
        bool doorThinX = core != null && core.b.size.x < core.b.size.z;
        const float s2 = 1.1f;                                     // overall size of the model in the hall
        root.transform.localScale = Vector3.one * s2;
        root.transform.rotation = Quaternion.Euler(0f, doorThinX ? 90f : 0f, 0f);   // door face must point toward the player (-Z)
        float floorY = all.size.y * 0.5f * scale * s2;              // lowest point of the model sits on the floor
        root.transform.position = new Vector3(VaultCenter.x, floorY, VaultCenter.z);
        GameObject frameGo = new GameObject("Frame"); frameGo.transform.SetParent(root.transform, false);
        Transform hinge = new GameObject("Hinge").transform; hinge.SetParent(root.transform, false);
        Vector3 hp = core != null ? core.b.center : Vector3.zero;
        if (core != null) { if (doorThinX) hp.z = core.b.min.z; else hp.x = core.b.min.x; hp.y = 0f; }
        hinge.localPosition = hp;
        foreach (Piece p in pieces)
        {
            GameObject go = new GameObject(p.name);
            go.AddComponent<MeshFilter>().sharedMesh = p.mesh;
            go.AddComponent<MeshRenderer>().sharedMaterial = p.mat;
            go.transform.SetParent(root.transform, false);
            go.transform.SetParent(door.Contains(p) ? hinge : frameGo.transform, true);
        }

        // 4. hide the old coded vault and hook the new door to the sequence
        VaultSequence seq = Object.FindFirstObjectByType<VaultSequence>();
        if (seq == null) { Debug.LogError("VaultSequence not found."); return; }
        SerializedObject so = new SerializedObject(seq);
        SerializedProperty dp = so.FindProperty("doorAssembly");
        Transform oldDoor = dp != null ? dp.objectReferenceValue as Transform : null;
        if (oldDoor == null && dp != null && dp.objectReferenceValue is GameObject og) oldDoor = og.transform;
        if (oldDoor != null && oldDoor != hinge)
        {
            Transform oldRoot = oldDoor.parent;
            oldDoor.gameObject.SetActive(false);
            if (oldRoot != null)
                foreach (Renderer rd in oldRoot.GetComponentsInChildren<Renderer>(true))
                {
                    if (rd.transform.IsChildOf(oldDoor) || rd.transform.IsChildOf(root.transform)) continue;
                    string n = rd.name.ToLower();
                    if (n.Contains("amber") || n.Contains("status") || n.Contains("behind") || n.Contains("lamp")) continue;
                    if (n.Contains("outer") || n.Contains("rim") || n.Contains("frame") || n.Contains("ring") || n.Contains("wall") || n.Contains("surround"))
                    { rd.enabled = false; Debug.Log("Hid old vault part: " + rd.name); }
                }
        }
        if (dp != null)
        {
            if (dp.propertyType == SerializedPropertyType.ObjectReference)
                dp.objectReferenceValue = hinge;
            so.ApplyModifiedProperties();
        }
        EditorSceneManager.MarkSceneDirty(SceneManager.GetActiveScene());
        EditorSceneManager.SaveScene(SceneManager.GetActiveScene());
        Debug.Log("Downloaded vault added: " + pieces.Count + " pieces, " + door.Count + " belong to the door. Press Play. If it faces the wrong way run '7b - Flip Vault 180'.");
    }

    [MenuItem("The Last Protocol/7b - Flip Vault 180")]
    static void Flip()
    {
        GameObject g = GameObject.Find("GlbVault");
        if (g == null) { Debug.LogWarning("Run step 7 first."); return; }
        g.transform.Rotate(0f, 180f, 0f);
        EditorSceneManager.MarkSceneDirty(SceneManager.GetActiveScene());
        EditorSceneManager.SaveScene(SceneManager.GetActiveScene());
    }
}
#endif
