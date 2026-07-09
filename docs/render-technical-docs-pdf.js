const { readFileSync, existsSync, mkdirSync } = require("fs");
const { resolve, dirname } = require("path");
const { spawnSync } = require("child_process");
const { pathToFileURL } = require("url");

const root = resolve(__dirname, "..");
const pluginMain = resolve(root, "myavana-hair-journey.php");
const htmlPath = resolve(__dirname, "myavana-technical-documentation.html");

function getVersion() {
  const source = readFileSync(pluginMain, "utf8");
  const match = source.match(/Version:\s*([0-9.]+)/);
  return match ? match[1] : "unknown";
}

function getChromePath() {
  const candidates = [
    "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome",
    "/Applications/Chromium.app/Contents/MacOS/Chromium",
  ];

  for (const candidate of candidates) {
    if (existsSync(candidate)) {
      return candidate;
    }
  }

  throw new Error("No supported Chrome/Chromium binary found.");
}

const version = getVersion();
const outputPath = resolve(__dirname, `myavana-technical-documentation-v${version}.pdf`);
const chromePath = getChromePath();

mkdirSync(dirname(outputPath), { recursive: true });

const args = [
  "--headless=new",
  "--disable-gpu",
  "--allow-file-access-from-files",
  "--print-to-pdf-no-header",
  `--print-to-pdf=${outputPath}`,
  "--run-all-compositor-stages-before-draw",
  "--virtual-time-budget=5000",
  pathToFileURL(htmlPath).href,
];

const result = spawnSync(chromePath, args, {
  cwd: root,
  encoding: "utf8",
});

if (result.status !== 0) {
  const message = result.stderr || result.stdout || "Unknown Chrome export error";
  throw new Error(message.trim());
}

process.stdout.write(`${outputPath}\n`);
