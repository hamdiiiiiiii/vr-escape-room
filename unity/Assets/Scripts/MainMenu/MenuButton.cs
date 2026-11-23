using TMPro;
using UnityEngine;
using UnityEngine.EventSystems;
using UnityEngine.UI;

// Smooth hover / select effect for the big main-menu buttons:
// the amber bar glows, a fill sweeps across, the label brightens and the button nudges forward.
[RequireComponent(typeof(Button))]
public class MenuButton : MonoBehaviour, IPointerEnterHandler, IPointerExitHandler, ISelectHandler, IDeselectHandler
{
    public RectTransform hoverFill;          // amber sweep behind the text
    public Image accentBar;                  // thin bar on the left
    public TMP_Text label;
    public TMP_Text chevron;                 // the ">" on the right

    public Color normalText = new Color(0.82f, 0.86f, 0.9f);
    public Color hoverText = Color.white;
    public Color normalBar = new Color(1f, 0.5f, 0.1f, 0.55f);
    public Color hoverBar = new Color(1f, 0.62f, 0.2f, 1f);
    public float fillWidth = 560f;
    public float slide = 16f;
    public float speed = 12f;

    RectTransform rt;
    Vector2 basePos;
    float t;           // 0 = idle, 1 = hovered
    float target;
    bool hasBase;

    void Awake() { rt = (RectTransform)transform; }

    void Start()
    {
        basePos = rt.anchoredPosition;
        hasBase = true;
        Apply(0f);
    }

    void OnEnable() { t = 0f; target = 0f; if (hasBase) Apply(0f); }

    public void OnPointerEnter(PointerEventData e) { Hover(true); }
    public void OnPointerExit(PointerEventData e) { Hover(false); }
    public void OnSelect(BaseEventData e) { Hover(true); }
    public void OnDeselect(BaseEventData e) { Hover(false); }

    void Hover(bool on)
    {
        if (on && target < 0.5f && MenuManager.Instance != null) MenuManager.Instance.PlayHover();
        target = on ? 1f : 0f;
    }

    void Update()
    {
        if (Mathf.Approximately(t, target)) return;
        t = Mathf.MoveTowards(t, target, Time.unscaledDeltaTime * speed);
        Apply(t);
    }

    void Apply(float k)
    {
        float e = Mathf.SmoothStep(0f, 1f, k);
        if (hoverFill != null) hoverFill.sizeDelta = new Vector2(fillWidth * e, hoverFill.sizeDelta.y);
        if (accentBar != null) accentBar.color = Color.Lerp(normalBar, hoverBar, e);
        if (label != null) label.color = Color.Lerp(normalText, hoverText, e);
        if (chevron != null) { Color c = chevron.color; c.a = e; chevron.color = c; }
        if (hasBase) rt.anchoredPosition = basePos + new Vector2(slide * e, 0f);
    }
}
