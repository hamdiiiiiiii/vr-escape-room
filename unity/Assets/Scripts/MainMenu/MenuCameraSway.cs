using UnityEngine;

// A very slow, subtle camera drift so the still scene feels alive (like a cinematic handheld).
public class MenuCameraSway : MonoBehaviour
{
    public float positionAmount = 0.06f;
    public float rotationAmount = 0.35f;     // degrees
    public float speed = 0.18f;

    public Vector3 pushInTarget;             // optional: used by the Enter animation later
    Vector3 basePos;
    Quaternion baseRot;

    void Start()
    {
        basePos = transform.position;
        baseRot = transform.rotation;
    }

    void Update()
    {
        float t = Time.time * speed;
        float nx = Mathf.PerlinNoise(t, 0.1f) - 0.5f;
        float ny = Mathf.PerlinNoise(0.7f, t) - 0.5f;
        float nz = Mathf.PerlinNoise(t, 5.3f) - 0.5f;

        transform.position = basePos + new Vector3(nx, ny * 0.6f, nz) * (positionAmount * 2f);
        transform.rotation = baseRot * Quaternion.Euler(ny * rotationAmount * 2f, nx * rotationAmount * 2f, 0f);
    }

    // Lets other scripts move the base position (the menu push-in) without fighting the sway.
    public void SetBase(Vector3 position, Quaternion rotation)
    {
        basePos = position;
        baseRot = rotation;
    }

    public Vector3 BasePosition { get { return basePos; } }
    public Quaternion BaseRotation { get { return baseRot; } }
}
