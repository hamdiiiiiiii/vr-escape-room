using UnityEditor;
using UnityEngine;

public static class QuestDeviceProbe
{
    public static string Check() { return Adb("devices -l", 10000, false); }

    public static string Adb(string arguments, int timeout = 30000, bool targetDevice = true)
    {
        string adb = System.IO.Path.Combine(UnityEditor.Android.AndroidExternalToolsSettings.sdkRootPath, "platform-tools/adb");
        if (!System.IO.File.Exists(adb)) return "Android adb executable not found: " + adb;
        if (targetDevice) arguments = "-s 1WMHHB48732163 " + arguments;
        var output = new System.Text.StringBuilder();
        var info = new System.Diagnostics.ProcessStartInfo(adb, arguments)
        {
            UseShellExecute = false,
            RedirectStandardOutput = true,
            RedirectStandardError = true,
            CreateNoWindow = true
        };
        using (var process = new System.Diagnostics.Process { StartInfo = info })
        {
            process.OutputDataReceived += (sender, e) => { if (e.Data != null) lock (output) output.AppendLine(e.Data); };
            process.ErrorDataReceived += (sender, e) => { if (e.Data != null) lock (output) output.AppendLine(e.Data); };
            process.Start(); process.BeginOutputReadLine(); process.BeginErrorReadLine();
            if (!process.WaitForExit(timeout)) return "Command still running after timeout: " + arguments;
            process.WaitForExit();
            return "Exit code: " + process.ExitCode + "\n" + output;
        }
    }

    public static void QueueBuild()
    {
        if (UnityEditor.BuildPipeline.isBuildingPlayer) throw new System.InvalidOperationException("Build already running.");
        SessionState.SetString("QuestTest.BuildState", "Queued");
        EditorApplication.delayCall += Build;
    }

    private static void Build()
    {
        try
        {
            SessionState.SetString("QuestTest.BuildState", "Building");
            System.IO.Directory.CreateDirectory("Builds");
            var scenes = new System.Collections.Generic.List<string>();
            foreach (var scene in EditorBuildSettings.scenes) if (scene.enabled) scenes.Add(scene.path);
            var options = new UnityEditor.BuildPlayerOptions
            {
                scenes = scenes.ToArray(),
                locationPathName = "Builds/MuseumQuestTest.apk",
                target = BuildTarget.Android,
                options = BuildOptions.Development
            };
            var report = BuildPipeline.BuildPlayer(options);
            string state = report.summary.result.ToString();
            SessionState.SetString("QuestTest.BuildState", state);
            SessionState.SetString("QuestTest.BuildReport", "Result: " + state + ", errors: " + report.summary.totalErrors
                + ", warnings: " + report.summary.totalWarnings + ", output: " + report.summary.outputPath);
            Debug.Log("[QuestTest] " + SessionState.GetString("QuestTest.BuildReport", ""));
        }
        catch (System.Exception ex)
        {
            SessionState.SetString("QuestTest.BuildState", "Failed");
            SessionState.SetString("QuestTest.BuildReport", ex.ToString());
            Debug.LogError("[QuestTest] " + ex);
        }
    }
}
