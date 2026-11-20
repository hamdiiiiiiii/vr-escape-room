using UnityEngine;

// The red warning beacon above the vault: a spinning spotlight and a glowing lens that blink.
// SetAmber() is called when the vault starts opening (the beacon changes from red to amber).
public class MenuBeacon : MonoBehaviour
{
    [Header("Parts")]
    public Transform rotor;          // spins around Y; carries the spotlight
    public Light beam;               // the spotlight on the rotor
    public Light glow;               // soft point light around the lens
    public Renderer lens;            // the glass dome

    [Header("Look")]
    public Color alertColor = new Color(1f, 0.08f, 0.05f);
    public Color amberColor = new Color(1f, 0.55f, 0.1f);
    public float spinSpeed = 140f;
    public float blinkSpeed = 3.2f;
    public float lensIntensity = 5f;
    public float beamIntensity = 14f;

    static readonly int EmissionId = Shader.PropertyToID("_EmissionColor");
    Color current;
    MaterialPropertyBlock block;
    bool amber;

    void Awake()
    {
        current = alertColor;
        block = new MaterialPropertyBlock();
    }

    public void SetAmber()
    {
        amber = true;
        blinkSpeed = 1.2f;       // calmer, steady glow
        spinSpeed = 60f;
    }

    void Update()
    {
        if (rotor != null) rotor.Rotate(0f, spinSpeed * Time.deltaTime, 0f, Space.Self);

        current = Color.Lerp(current, amber ? amberColor : alertColor, Time.deltaTime * 3f);

        // 0..1 smooth blink; stays brighter when amber
        float f = (Mathf.Sin(Time.time * blinkSpeed * Mathf.PI) + 1f) * 0.5f;
        f = Mathf.Lerp(amber ? 0.6f : 0.08f, 1f, f * f);

        if (beam != null) { beam.color = current; beam.intensity = beamIntensity * f; }
        if (glow != null) { glow.color = current; glow.intensity = 3f * f; }
        if (lens != null)
        {
            lens.GetPropertyBlock(block);
            block.SetColor(EmissionId, current * (lensIntensity * f));
            lens.SetPropertyBlock(block);
        }
    }
}
