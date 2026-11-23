#if UNITY_EDITOR
using System.Text;
using UnityEditor;
using UnityEngine;

// Menu: The Last Protocol > Inspect Vault Model
// Prints the parts of the downloaded vault model (names, positions, sizes) to the Console.
// Read-only: it changes nothing in your project or scene.
public static class InspectVaultModel
{
    const string Path = "Assets/MainMenu/Imported/bank_vault__armored_door__low_poly.glb";

    [MenuItem("The Last Protocol/Inspect Vault Model")]
    static void Inspect()
    {
        GameObject model = AssetDatabase.LoadAssetAtPath<GameObject>(Path);
        if (model == null) { Debug.LogWarning("Vault model not found or not imported: " + Path); return; }

        StringBuilder sb = new StringBuilder("VAULT MODEL PARTS\n");
        Walk(model.transform, 0, sb);
        Debug.Log(sb.ToString());
    }

    static void Walk(Transform t, int depth, StringBuilder sb)
    {
        string line = new string(' ', depth * 2) + t.name
            + "  pos=" + t.localPosition.ToString("F2")
            + "  rot=" + t.localEulerAngles.ToString("F0")
            + "  scl=" + t.localScale.ToString("F2");

        MeshFilter mf = t.GetComponent<MeshFilter>();
        if (mf != null && mf.sharedMesh != null)
            line += "  mesh=" + mf.sharedMesh.name + " size=" + mf.sharedMesh.bounds.size.ToString("F2") + " tris=" + (mf.sharedMesh.triangles.Length / 3);

        sb.AppendLine(line);
        foreach (Transform c in t) Walk(c, depth + 1, sb);
    }
}
#endif
