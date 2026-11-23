using System.Collections;
using UnityEngine;
using UnityEngine.SceneManagement;

public class RoomLoader : MonoBehaviour
{
    [SerializeField] string[] scenesToLoad = { "Museum_Puzzles" };
    [SerializeField] string activeSceneName = "Museum Main";

    IEnumerator Start()
    {
        foreach (var s in scenesToLoad)
        {
            if (SceneManager.GetSceneByName(s).isLoaded) continue;
            if (!Application.CanStreamedLevelBeLoaded(s))
            {
                Debug.LogWarning("RoomLoader: scene not in Build Settings: " + s);
                continue;
            }
            yield return SceneManager.LoadSceneAsync(s, LoadSceneMode.Additive);
        }
        var active = SceneManager.GetSceneByName(activeSceneName);
        if (active.isLoaded) SceneManager.SetActiveScene(active);
    }
}