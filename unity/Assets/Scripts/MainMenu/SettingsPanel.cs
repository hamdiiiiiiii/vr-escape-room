using TMPro;
using UnityEngine;
using UnityEngine.UI;

// The settings panel: master volume, graphics quality and fullscreen. Choices are saved with PlayerPrefs.
public class SettingsPanel : MonoBehaviour
{
    public Slider volumeSlider;
    public TMP_Text volumeValue;
    public Button qualityPrev, qualityNext;
    public TMP_Text qualityValue;
    public Button fullscreenButton;
    public TMP_Text fullscreenValue;

    const string VolumeKey = "MasterVolume";
    const string QualityKey = "QualityLevel";

    void Start()
    {
        // volume
        float volume = PlayerPrefs.GetFloat(VolumeKey, 0.8f);
        if (volumeSlider != null)
        {
            volumeSlider.SetValueWithoutNotify(volume);
            volumeSlider.onValueChanged.AddListener(SetVolume);
        }
        SetVolume(volume);

        // graphics quality (only the levels defined in Project Settings > Quality)
        int level = PlayerPrefs.GetInt(QualityKey, QualitySettings.GetQualityLevel());
        level = Mathf.Clamp(level, 0, QualitySettings.names.Length - 1);
        SetQuality(level);
        if (qualityPrev != null) qualityPrev.onClick.AddListener(() => { Click(); SetQuality((QualitySettings.GetQualityLevel() - 1 + QualitySettings.names.Length) % QualitySettings.names.Length); });
        if (qualityNext != null) qualityNext.onClick.AddListener(() => { Click(); SetQuality((QualitySettings.GetQualityLevel() + 1) % QualitySettings.names.Length); });

        // fullscreen (a window toggle that only matters in a PC build)
        if (fullscreenButton != null) fullscreenButton.onClick.AddListener(() => { Click(); ToggleFullscreen(); });
        UpdateFullscreenText();
    }

    void Click() { if (MenuManager.Instance != null) MenuManager.Instance.PlayClick(); }

    void SetVolume(float v)
    {
        AudioListener.volume = v;
        PlayerPrefs.SetFloat(VolumeKey, v);
        if (volumeValue != null) volumeValue.text = Mathf.RoundToInt(v * 100f) + "%";
    }

    void SetQuality(int level)
    {
        QualitySettings.SetQualityLevel(level, true);
        PlayerPrefs.SetInt(QualityKey, level);
        if (qualityValue != null) qualityValue.text = QualitySettings.names[level].ToUpper();
    }

    void ToggleFullscreen()
    {
        Screen.fullScreen = !Screen.fullScreen;
        UpdateFullscreenText();
    }

    void UpdateFullscreenText()
    {
        if (fullscreenValue != null) fullscreenValue.text = Screen.fullScreen ? "FULLSCREEN" : "WINDOWED";
    }
}
