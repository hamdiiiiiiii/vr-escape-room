using UnityEngine;

// Makes a light and/or a glowing material pulse or flicker. Used for LEDs, alarm lights and neon.
public class PulseGlow : MonoBehaviour
{
    public float speed = 2f;
    [Range(0f, 1f)] public float minFactor = 0.3f;
    public bool flicker = false;
    public Color emission = Color.white;
    public float intensity = 2f;

    static readonly int EmissionId = Shader.PropertyToID("_EmissionColor");

    Renderer rend;
    Light lamp;
    float baseLight;
    float phase;
    MaterialPropertyBlock block;

    void Start()
    {
        rend = GetComponent<Renderer>();
        lamp = GetComponent<Light>();
        if (lamp != null) baseLight = lamp.intensity;
        phase = Random.value * 10f;
        block = new MaterialPropertyBlock();
    }

    void Update()
    {
        float f;
        if (flicker)
            f = Mathf.PerlinNoise(Time.time * speed, phase) > 0.35f ? 1f : minFactor;
        else
            f = Mathf.Lerp(minFactor, 1f, (Mathf.Sin(Time.time * speed + phase) + 1f) * 0.5f);

        if (lamp != null) lamp.intensity = baseLight * f;

        if (rend != null)
        {
            rend.GetPropertyBlock(block);
            block.SetColor(EmissionId, emission * (intensity * f));
            rend.SetPropertyBlock(block);
        }
    }
}
