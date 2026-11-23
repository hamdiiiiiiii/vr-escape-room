using UnityEngine;

// A little bug that wanders around the room, faces where it flies and flaps its wings.
// The wings are two child objects named "WingL" and "WingR".
public class FlyingBug : MonoBehaviour
{
    public Vector3 center = new Vector3(0f, 1.8f, 1f);
    public Vector3 size = new Vector3(9f, 2.2f, 7f);
    public float speed = 1.2f;
    public float flapSpeed = 28f;

    Vector3 target;
    Vector3 velocity;
    Transform wingL;
    Transform wingR;
    float phase;

    void Start()
    {
        wingL = transform.Find("WingL");
        wingR = transform.Find("WingR");
        phase = Random.value * 10f;
        speed *= Random.Range(0.7f, 1.4f);
        velocity = Random.onUnitSphere * speed;
        target = PickTarget();
    }

    Vector3 PickTarget()
    {
        return center + new Vector3(
            Random.Range(-0.5f, 0.5f) * size.x,
            Random.Range(-0.5f, 0.5f) * size.y,
            Random.Range(-0.5f, 0.5f) * size.z);
    }

    void Update()
    {
        Vector3 toTarget = target - transform.position;
        if (toTarget.magnitude < 0.4f) target = PickTarget();

        Vector3 desired = toTarget.normalized * speed;
        velocity = Vector3.Lerp(velocity, desired, Time.deltaTime * 1.5f);

        float bob = Mathf.Sin(Time.time * 6f + phase) * 0.35f;
        transform.position += (velocity + Vector3.up * bob) * Time.deltaTime;

        if (velocity.sqrMagnitude > 0.01f)
        {
            Quaternion look = Quaternion.LookRotation(velocity.normalized, Vector3.up);
            transform.rotation = Quaternion.Slerp(transform.rotation, look, Time.deltaTime * 4f);
        }

        float flap = Mathf.Sin(Time.time * flapSpeed + phase) * 40f;
        if (wingL != null) wingL.localRotation = Quaternion.Euler(0f, 0f, flap);
        if (wingR != null) wingR.localRotation = Quaternion.Euler(0f, 0f, -flap);
    }
}
