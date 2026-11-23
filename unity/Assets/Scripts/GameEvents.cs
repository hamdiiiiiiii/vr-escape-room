using System;

public static class GameEvents
{
    public static event Action PowerRestored;
    public static void RaisePowerRestored() => PowerRestored?.Invoke();
}