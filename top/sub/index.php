<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../models/User.php";

$hash = $_GET["hash"] ?? "";

if (empty($hash)) {
  http_response_code(404);
  die("Not found");
}

$userModel = new User();
$user = $userModel->getByHash($hash);

if (!$user) {
  http_response_code(404);
  die("Subscription not found");
}

$output = "";

if ($user["use_fetch"] == 1 && !empty($user["fetch_url_sub"])) {
  $fetchUrl = trim($user["fetch_url_sub"]);

if (
  strpos($fetchUrl, "https://catcode.ir") === 0 ||
  strpos($fetchUrl, "https://sub.catcode.ir") === 0
){
    // Force base64/links format regardless of User-Agent
    // نکته: مسیر /v2ray دیگه معتبر نیست؛ سرور الان فقط این مقادیر رو قبول می‌کنه:
    // links, links_base64, xray, wireguard, sing_box, clash, clash_meta, outline, block
    // برای همون خروجی base64 که قبلا با /v2ray می‌گرفتیم، الان باید /links_base64 بزنیم.
    $v2rayUrl = rtrim($fetchUrl, "/") . "/links_base64";

    $ch = curl_init();
    curl_setopt_array($ch, [
      CURLOPT_URL => $v2rayUrl,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_SSL_VERIFYHOST => false,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_TIMEOUT => 6,
      CURLOPT_CONNECTTIMEOUT => 4,
      CURLOPT_USERAGENT => "v2rayNG/1.8.24",
      CURLOPT_HTTPHEADER => ["Accept: */*", "Connection: keep-alive"],
      CURLOPT_ENCODING => "",
    ]);

    // اتصال به این سرویس گاهی ناپایداره، پس ۲ بار retry می‌کنیم
    // ولی timeoutها رو خیلی کوتاه نگه می‌داریم که PHP worker زیاد گیر نکنه
    // (بدترین حالت الان: 2 × 6 ثانیه = 12 ثانیه، نه 90 ثانیه)
    $response = false;
    $httpCode = 0;
    $maxAttempts = 2;
    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
      $response = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      if ($response !== false && $httpCode == 200) {
        break;
      }
      if ($attempt < $maxAttempts) {
        usleep(500000); // نیم ثانیه صبر قبل از تلاش بعدی
      }
    }
    curl_close($ch);

    if ($response && $httpCode == 200) {
      $trimmed = trim($response);

      // Detect JSON response (fallback if endpoint ever returns an error/JSON body)
      if ($trimmed[0] === '[' || $trimmed[0] === '{') {
        $parsed = parseMarzbанSubscription($trimmed, $user["name"], $user["updated_at"]);
        $output = $parsed ?? base64_decode($user["subscription_code"] ?? "");
      } else {
        $decoded = base64_decode($trimmed, true);
        $output = $decoded !== false
          ? renameConfig3($decoded, $user["name"], $user["updated_at"])
          : $response;
      }
    } else {
      $output = base64_decode($user["subscription_code"] ?? "");
    }
  } elseif (strpos($fetchUrl, "https://p2.silkroadway") === 0) {
    $ch = curl_init();
    curl_setopt_array($ch, [
      CURLOPT_URL => $fetchUrl,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_SSL_VERIFYHOST => false,
      CURLOPT_USERAGENT => "v2rayNG/1.8.0",
      CURLOPT_HTTPHEADER => ["Accept: */*", "Connection: keep-alive"],
      CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200 && $response) {
      $decoded = base64_decode($response);
      $output = processSubscription($decoded, $user["name"]);
    } else {
      $output = base64_decode($user["subscription_code"] ?? "");
    }
  } else {
    $ch = curl_init();
    curl_setopt_array($ch, [
      CURLOPT_URL => $fetchUrl,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_SSL_VERIFYHOST => false,
      CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 10.0; Win64; x64)",
      CURLOPT_TIMEOUT => 15,
    ]);
    $raw_response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200 && $raw_response) {
      $decoded = base64_decode($raw_response);
      $lines = explode("\n", trim($decoded));
      $processed = [];
      foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        $processed[] = renameConfig2($line, $user["name"], $user["updated_at"]);
      }
      $output = implode("\n", $processed);
    } else {
      $output = base64_decode($user["subscription_code"] ?? "");
    }
  }
} else {
  $output = base64_decode($user["subscription_code"] ?? "");
}

header("Content-Type: text/plain; charset=utf-8");
echo base64_encode($output);
exit();

function parseMarzbанSubscription($json, $userName, $createdAt = null)
{
  $configs = json_decode($json, true);
  if (!is_array($configs)) return null;

  $expiredConfig =
    "ss://Y2hhY2hhMjAtaWV0Zi1wb2x5MTMwNTpZYUkzWkl6OXhJQ1BfVGFmaWtjeEdTRGhCbFFpMWxZQw==@1.1.1.1:53#%F0%9F%94%B4%D8%A7%D8%B4%D8%AA%D8%B1%D8%A7%DA%A9%20%D8%B4%D9%85%D8%A7%20%D8%A8%D9%87%20%D9%BE%D8%A7%DB%8C%D8%A7%D9%86%20%D8%B1%D8%B3%DB%8C%D8%AF%D9%87";

  $lines = [];
  $usageInfo = "";

  foreach ($configs as $item) {
    if (!is_array($item)) continue;

    $remarks = $item["remarks"] ?? "";

    // Extract usage info from last items (e.g. "30.0 GB / 30.0 GB")
    if (preg_match('/[\d.]+ GB \/ [\d.]+ GB/', $remarks)) {
      $usageInfo = $remarks;
      continue;
    }

    $protocol = $item["protocol"] ?? "";
    $address  = $item["settings"]["servers"][0]["address"] ?? ($item["settings"]["vnext"][0]["address"] ?? "");
    $port     = $item["settings"]["servers"][0]["port"]    ?? ($item["settings"]["vnext"][0]["port"]    ?? "");

    if (empty($protocol) || empty($address)) continue;

    $tag = urlencode("[$userName] " . $remarks);

    switch ($protocol) {
      case "vmess":
        $vnext = $item["settings"]["vnext"][0] ?? [];
        $user_obj = $vnext["users"][0] ?? [];
        $stream = $item["streamSettings"] ?? [];
        $obj = [
          "v"    => "2",
          "ps"   => "[$userName] $remarks",
          "add"  => $address,
          "port" => (string)$port,
          "id"   => $user_obj["id"] ?? "",
          "aid"  => (string)($user_obj["alterId"] ?? 0),
          "net"  => $stream["network"] ?? "tcp",
          "type" => "none",
          "host" => $stream["wsSettings"]["headers"]["Host"] ?? "",
          "path" => $stream["wsSettings"]["path"] ?? "",
          "tls"  => ($stream["security"] ?? "") === "tls" ? "tls" : "",
        ];
        $lines[] = "vmess://" . base64_encode(json_encode($obj));
        break;

      case "vless":
        $vnext = $item["settings"]["vnext"][0] ?? [];
        $user_obj = $vnext["users"][0] ?? [];
        $stream = $item["streamSettings"] ?? [];
        $uuid = $user_obj["id"] ?? "";
        $net  = $stream["network"] ?? "tcp";
        $tls  = ($stream["security"] ?? "") === "tls" ? "tls" : "none";
        $path = urlencode($stream["wsSettings"]["path"] ?? "");
        $host = urlencode($stream["wsSettings"]["headers"]["Host"] ?? "");
        $lines[] = "vless://$uuid@$address:$port?encryption=none&security=$tls&type=$net&host=$host&path=$path#$tag";
        break;

      case "trojan":
        $server = $item["settings"]["servers"][0] ?? [];
        $password = $server["password"] ?? "";
        $stream = $item["streamSettings"] ?? [];
        $net  = $stream["network"] ?? "tcp";
        $tls  = ($stream["security"] ?? "") === "tls" ? "tls" : "none";
        $path = urlencode($stream["wsSettings"]["path"] ?? "");
        $host = urlencode($stream["wsSettings"]["headers"]["Host"] ?? "");
        $lines[] = "trojan://$password@$address:$port?security=$tls&type=$net&host=$host&path=$path#$tag";
        break;

      case "shadowsocks":
        $server = $item["settings"]["servers"][0] ?? [];
        $method = $server["method"] ?? "chacha20-ietf-poly1305";
        $pass   = $server["password"] ?? "";
        $userinfo = base64_encode("$method:$pass");
        $lines[] = "ss://$userinfo@$address:$port#$tag";
        break;
    }
  }

  if (empty($lines)) {
    return $expiredConfig;
  }

  // Append usage info as a comment config if available
  if ($usageInfo) {
    $lines[] = "ss://Y2hhY2hhMjAtaWV0Zi1wb2x5MTMwNTpwbGFjZWhvbGRlcg==@1.1.1.1:1#" .
      urlencode("📊 " . $usageInfo);
  }

  return implode("\n", $lines);
}

function processSubscription($content, $userName)
{
  $lines = explode("\n", trim($content));
  $processed = [];
  $hasVolumeLine = false;

  foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line)) {
      continue;
    }
    $decoded = urldecode($line);
    if (preg_match("/حجم\s*باقی/u", $decoded)) {
      $hasVolumeLine = true;
      $processed[] = multiplyVolume($line);
    } elseif (preg_match("/(روز|%D8%B1%D9%88%D8%B2)/i", $line)) {
      $processed[] = $line;
    } else {
      $processed[] = renameConfig($line, $userName);
    }
  }

  if (!$hasVolumeLine) {
    return "ss://Y2hhY2hhMjAtaWV0Zi1wb2x5MTMwNTpZYUkzWkl6OXhJQ1BfVGFmaWtjeEdTRGhCbFFpMWxZQw==@1.1.1.1:53#%F0%9F%94%B4%D8%A7%D8%B4%D8%AA%D8%B1%D8%A7%DA%A9%20%D8%B4%D9%85%D8%A7%20%D8%A8%D9%87%20%D9%BE%D8%A7%DB%8C%D8%A7%D9%86%20%D8%B1%D8%B3%DB%8C%D8%AF%20";
  }

  return implode("\n", $processed);
}

function multiplyVolume($line)
{
  $line = urldecode($line);
  return preg_replace_callback(
    "/([\d.]+)\s*MB/",
    function ($m) {
      return number_format(floatval($m[1]) * 3, 2, ".", "") . " MB";
    },
    $line,
  );
}

function renameConfig($config, $userName)
{
  if (strpos($config, "#") !== false) {
    $parts = explode("#", $config, 2);
    return $parts[0] . "#" . $userName . " ✨";
  }
  return $config;
}

function renameConfig2($config, $userName, $createdAt = null)
{
  $baseConfig =
    "ss://Y2hhY2hhMjAtaWV0Zi1wb2x5MTMwNTp6VEpGVHhWOTBjNHo3SmQ3NnhGOTBYU3NWUFVZeklWUw==@3.6.9.3:369#";
  $expiredConfig =
    "ss://Y2hhY2hhMjAtaWV0Zi1wb2x5MTMwNTpZYUkzWkl6OXhJQ1BfVGFmaWtjeEdTRGhCbFFpMWxZQw==@1.1.1.1:53#%F0%9F%94%B4%D8%A7%D8%B4%D8%AA%D8%B1%D8%A7%DA%A9%20%D8%B4%D9%85%D8%A7%20%D8%A8%D9%87%20%D9%BE%D8%A7%DB%8C%D8%A7%D9%86%20%D8%B1%D8%B3%DB%8C%D8%AF%20";

  if (strpos($config, "#") !== false) {
    $parts = explode("#", $config, 2);
    $name = urldecode($parts[1]);

    if (preg_match_all("/([\d\.]+)\s*(MB|GB)/iu", $name, $matches)) {
      $lastIndex = count($matches[0]) - 1;
      $volume = floatval($matches[1][$lastIndex]);
      $unit = strtoupper($matches[2][$lastIndex]);

      if ($unit === "GB") {
        $volume *= 1024;
      }
      $volume *= 3;

      if ($volume <= 0) {
        return $expiredConfig;
      }

      $createdTimestamp = strtotime($createdAt ?: date("Y-m-d H:i:s"));
      $daysPassed = floor((time() - $createdTimestamp) / 86400);
      $timeRemaining = 30 - $daysPassed;

      if ($timeRemaining <= 0) {
        return $expiredConfig;
      }

      $newVolumeStr = number_format($volume, 2, ".", "");
      return $baseConfig .
        rawurlencode($newVolumeStr . " MB📊 : حجم باقی") .
        "\n" .
        $baseConfig .
        rawurlencode($timeRemaining . " :  روز باقی") .
        "\n" .
        $parts[0] .
        "#" .
        rawurlencode($userName . " ✨");
    }

    return $expiredConfig;
  }

  return $config;
}

function renameConfig3($content, $userName, $createdAt = null)
{
  $lines = explode("\n", trim($content));

  $expiredConfig =
    "ss://Y2hhY2hhMjAtaWV0Zi1wb2x5MTMwNTpZYUkzWkl6OXhJQ1BfVGFmaWtjeEdTRGhCbFFpMWxZQw==@1.1.1.1:53#%F0%9F%94%B4%D8%A7%D8%B4%D8%AA%D8%B1%D8%A7%DA%A9%20%D8%B4%D9%85%D8%A7%20%D8%A8%D9%87%20%D9%BE%D8%A7%DB%8C%D8%A7%D9%86%20%D8%B1%D8%B3%DB%8C%D8%AF";

  // اگه آیتم اول (وضعیت اکانت) توی اسمش "غیرفعال" داشت، یعنی ساب غیرفعاله
  if (isset($lines[0])) {
    $firstParts = explode("#", $lines[0], 2);
    if (isset($firstParts[1])) {
      $firstName = urldecode($firstParts[1]);
      if (strpos($firstName, "غیرفعال") !== false) {
        return $expiredConfig;
      }
    }
  }

  // خط دوم، همون خط وضعیت/حجمه که دیگه ادیت یا ضرب نمی‌شه،
  // عیناً همونی که از ساب اومده پاس داده می‌شه (مثلاً "⏳ 180 | 🔋 0.14%")
  $item2Text = "";
  if (isset($lines[1])) {
    $parts = explode("#", $lines[1], 2);
    if (isset($parts[1])) {
      $item2Text = urldecode($parts[1]);
    }
  }

  $baseConfig =
    "ss://Y2hhY2hhMjAtaWV0Zi1wb2x5MTMwNTpidUFncXFoa3dsTHhLNGxZem1LdlE0ZWY0ZWpFNUxlaA==@3.6.9.3:369#";

  // فیلتر خطوط خالی
  $filtered = array_values(array_filter(
    array_map('trim', $lines),
    fn($l) => $l !== ""
  ));

  // جایگزینی متن داخل نام‌ها (بدون اضافه‌کردن userName)
  foreach ($filtered as &$line) {
    if (strpos($line, "#") !== false) {
      [$base, $name] = explode("#", $line, 2);
      $name = str_replace("(⅒)", "(حجم شما ⅕ محاسبه می شود)", urldecode($name));
      $line = $base . "#" . rawurlencode(trim($name));
    }
  }
  unset($line);

  $filtered = array_slice($filtered, 2);

  // اگه بعد از حذف ۲ خط وضعیت/حجم هیچ کانفیگ واقعی‌ای نمونده، یعنی ساب خالی/منقضی شده
  if (empty($filtered)) {
    return $expiredConfig;
  }

  $result   = [];
  $result[] = $baseConfig . rawurlencode("👤 " . $userName . " | ✅ Active");
  if ($item2Text !== "") {
    $result[] = $baseConfig . rawurlencode($item2Text);
  }
  foreach ($filtered as $line) {
    $result[] = $line;
  }

  return implode("\n", $result);
}
