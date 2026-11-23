using UnityEngine;
using UnityEngine.InputSystem;
using UnityEngine.SceneManagement;

// Used by the temporary EscapeRoom_Main placeholder: Esc (or the on-screen button) goes back to the main menu.
public class ReturnToMenu : MonoBehaviour
{
    public string menuScene = "MainMenu";

    void Update()
    {
        Keyboard kb = Keyboard.current;
        if (kb != null && kb.escapeKey.wasPressedThisFrame) Go();
    }

    public void Go()
    {
        if (Application.CanStreamedLevelBeLoaded(menuScene)) SceneManager.LoadScene(menuScene);
        else Debug.LogError("Scene '" + menuScene + "' is not in Build Settings.");
    }
}
