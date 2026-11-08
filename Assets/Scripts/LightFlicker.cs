using UnityEngine;

public class LightFlicker : MonoBehaviour
{
    [SerializeField] Light targetLight;
    [SerializeField] float minIntensity = 0.1f;
    [SerializeField] float maxIntensity = 1.5f;
    [SerializeField] float flickerSpeed = 8f;
    bool restored;
    float seed;

    void Awake()
    {
        if (!targetLight) targetLight = GetComponent<Light>();
        seed = Random.value * 100f;
    }

    void OnEnable()  { GameEvents.PowerRestored += RestorePower; }
    void OnDisable() { GameEvents.PowerRestored -= RestorePower; }

    void Update()
    {
        if (restored) { targetLight.intensity = maxIntensity; return; }
        float n = Mathf.PerlinNoise(seed, Time.time * flickerSpeed);
        targetLight.intensity = Mathf.Lerp(minIntensity, maxIntensity, n);
    }

    public void RestorePower() { restored = true; }
}