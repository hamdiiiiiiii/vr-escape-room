using UnityEngine;

// Makes an object float up and down slowly. Optionally keeps it facing the player's camera
// (used for the floating code symbols like { } and 404).
public class Drift : MonoBehaviour
{
    public float height = 0.15f;
    public float speed = 0.6f;
    public bool faceCamera = false;

    Vector3 start;
    float phase;

    void Start()
    {
        start = transform.position;
        phase = Random.value * 6.28f;
    }

    void Update()
    {
        transform.position = start + Vector3.up * Mathf.Sin(Time.time * speed + phase) * height;

        if (faceCamera && Camera.main != null)
        {
            Vector3 away = transform.position - Camera.main.transform.position;
            if (away.sqrMagnitude > 0.01f) transform.rotation = Quaternion.LookRotation(away, Vector3.up);
        }
    }
}
