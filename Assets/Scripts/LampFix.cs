using UnityEngine;
using UnityEngine.UI;
using UnityEngine.Events;

[RequireComponent(typeof(Button))]
public class LampFix : MonoBehaviour
{
    [SerializeField] private Image lampImage;
    [SerializeField] private Color onColor = Color.yellow;
    public UnityEvent onFixed;   // Station 2 will connect here later

    private bool fixedAlready = false;

    void Awake()
    {
        if (lampImage == null) lampImage = GetComponent<Image>();
        GetComponent<Button>().onClick.AddListener(Fix);
    }

    public void Fix()
    {
        if (fixedAlready) return;
        fixedAlready = true;
        lampImage.color = onColor;
        Debug.Log("Lamp 3 fixed!");
        onFixed.Invoke();
    }
}