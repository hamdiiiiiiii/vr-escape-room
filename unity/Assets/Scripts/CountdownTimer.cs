using UnityEngine;
using TMPro;

public class CountdownTimer : MonoBehaviour
{
    [SerializeField] private TMP_Text timerText;
    [SerializeField] private float startTime = 600f; // seconds (600 = 10 minutes)

    private float timeLeft;
    private bool running = true;

    void Start()
    {
        timeLeft = startTime;
        UpdateDisplay();
    }

    void Update()
    {
        if (!running) return;

        timeLeft -= Time.deltaTime;

        if (timeLeft <= 0f)
        {
            timeLeft = 0f;
            running = false;
            OnTimeUp();
        }

        UpdateDisplay();
    }

    void UpdateDisplay()
    {
        int total = Mathf.CeilToInt(timeLeft);
        int minutes = total / 60;
        int seconds = total % 60;
        timerText.text = $"{minutes:00}:{seconds:00}";
    }

    void OnTimeUp()
    {
        Debug.Log("Time's up!");
        // Later: show a "Game Over" screen or restart the scene here
    }

    public void StopTimer()  { running = false; }
    public void StartTimer() { running = true; }
    public void ResetTimer() { timeLeft = startTime; running = true; }
}