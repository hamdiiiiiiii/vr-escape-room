using System.Collections;
using UnityEngine;

// Makes the amber ring (and the other amber lights) pulse softly, and lets the menu
// power them up with Activate() when the player enters the facility.
public class VaultGlow : MonoBehaviour
{
    [Header("What glows")]
    public Renderer[] glowRenderers;     // amber ring, amber strips ...
    public Light[] glowLights;           // amber lights

    [Header("Look")]
    public Color color = new Color(1f, 0.55f, 0.12f);
    public float baseEmission = 2.2f;
    public float pulseDepth = 0.25f;     // 0.25 = 25 % brighter / dimmer
    public float pulseSpeed = 1.4f;
    public float baseLightIntensity = 5f;

    static readonly int EmissionId = Shader.PropertyToID("_EmissionColor");
    MaterialPropertyBlock block;
    float boost = 1f;                    // 1 = idle, grows when activated

    void Awake() { block = new MaterialPropertyBlock(); }

    void Update()
    {
        float pulse = 1f + Mathf.Sin(Time.time * pulseSpeed) * pulseDepth;
        float level = pulse * boost;

        if (glowRenderers != null)
        {
            foreach (Renderer r in glowRenderers)
            {
                if (r == null) continue;
                r.GetPropertyBlock(block);
                block.SetColor(EmissionId, color * (baseEmission * level));
                r.SetPropertyBlock(block);
            }
        }
        if (glowLights != null)
        {
            foreach (Light l in glowLights)
            {
                if (l == null) continue;
                l.color = color;
                l.intensity = baseLightIntensity * level;
            }
        }
    }

    // Power up: the glow swells over 'seconds'.
    public void Activate(float seconds = 1.5f, float targetBoost = 2.4f)
    {
        StopAllCoroutines();
        StartCoroutine(Swell(seconds, targetBoost));
    }

    IEnumerator Swell(float seconds, float target)
    {
        float start = boost, t = 0f;
        while (t < seconds)
        {
            t += Time.deltaTime;
            boost = Mathf.Lerp(start, target, Mathf.SmoothStep(0f, 1f, t / seconds));
            yield return null;
        }
        boost = target;
    }
}
