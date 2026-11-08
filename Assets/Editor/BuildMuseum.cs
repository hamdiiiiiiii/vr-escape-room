using UnityEditor;
using UnityEngine;

public static class BuildMuseum
{
    [MenuItem("Museum/Build Blockout")]
    static void Build()
    {
        var root = new GameObject("Museum");
        Undo.RegisterCreatedObjectUndo(root, "Build Museum");

        Cube(root, "Floor_Main",       new Vector3(0, -0.05f, 0),     new Vector3(10, 0.1f, 8));
        Cube(root, "Wall_Front",       new Vector3(0, 1.75f, -4),     new Vector3(10, 3.5f, 0.2f));
        Cube(root, "Wall_Left",        new Vector3(-5, 1.75f, 0),     new Vector3(0.2f, 3.5f, 8));
        Cube(root, "Wall_Right",       new Vector3(5, 1.75f, 0),      new Vector3(0.2f, 3.5f, 8));
        Cube(root, "Wall_Back_Left",   new Vector3(-2.875f, 1.75f, 4), new Vector3(4.25f, 3.5f, 0.2f));
        Cube(root, "Wall_Back_Right",  new Vector3(2.875f, 1.75f, 4),  new Vector3(4.25f, 3.5f, 0.2f));
        Cube(root, "Wall_Back_Top",    new Vector3(0, 2.85f, 4),      new Vector3(1.5f, 1.3f, 0.2f));
        Cube(root, "Exit Door",        new Vector3(0, 1.1f, 3.95f),   new Vector3(1.4f, 2.2f, 0.1f));
        Cube(root, "Station1_FuseBoxScreen", new Vector3(-3, 1.5f, 3.8f), new Vector3(1.6f, 1, 0.1f));
        Cube(root, "Station2_PartsTable",    new Vector3(3, 0.45f, 2),     new Vector3(1.5f, 0.9f, 0.8f));
    }

    static void Cube(GameObject parent, string name, Vector3 pos, Vector3 scale)
    {
        var c = GameObject.CreatePrimitive(PrimitiveType.Cube);
        c.name = name;
        c.transform.SetParent(parent.transform);
        c.transform.localPosition = pos;
        c.transform.localScale = scale;
        Undo.RegisterCreatedObjectUndo(c, "Create " + name);
    }
}