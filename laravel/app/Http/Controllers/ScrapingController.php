<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Goutte\Client;

class ScrapingController extends Controller
{
  public function signin(Request $request)
  {
    // データの初期化
    $data = ""; // リンク

    // Goutte\Client
    $client = new Client();

    try {
      // ログイン処理
      $login_page = $client->request("GET", (string) env("APP_LOGIN"));
      $login_form = $login_page->filter("form")->form();
      $login_form["id"] = $request->input("id");
      $login_form["pw"] = $request->input("pw");
      $client->submit($login_form);

      $crawler = $client->request("GET", (string) env("APP_LOGIN"));

      // データの取得
      $data = $crawler->filter(".home_back")->text();

      if ($data) {
        return response(["status" => 200, "message" => "success"], 200);
      }
    } catch (\Exception $e) {
      return response(
        ["status" => 401, "message" => "unauthorized error"],
        401
      );
    }
  }

  public function news(Request $request)
  {
    // データの初期化
    $links = []; // リンク
    $titles = []; // タイトル
    $dates = []; // 日付
    $tags = []; // タグ
    $ids = []; // id

    // Goutte\Client
    $client = new Client();

    // ログイン処理
    $login_page = $client->request("GET", (string) env("APP_LOGIN"));
    $login_form = $login_page->filter("form")->form();
    $login_form["id"] = $request->input("id");
    $login_form["pw"] = $request->input("pw");
    $client->submit($login_form);

    // サイトデータ取得
    $crawler = $client->request("GET", (string) env("APP_NEWS"));

    // データの取得
    $crawler
      ->filter("ul.news_list01 li")
      ->each(function ($node) use (&$links, &$titles, &$tags, &$dates, &$ids) {
        $titles[] = $node->filter("dd")->text(); // タイトル
        $dates[] = strstr($node->filter("dt")->text(), " ", true); // date 空白から左
        $tags[] = $node->filter("span")->text(); // タグ
        $links[] =
          (string) env("APP_NEWS_LINK") .
          strstr($node->filter("a")->attr("href"), "?"); // リンク
        $ids[] = preg_replace("/[^0-9]/", "", $node->filter("a")->attr("href")); // id urlから数字のみ
      });

    // 取得したデータをjsonにする
    foreach ($ids as $i => $id) {
      $params[] = [
        "id" => $id, // id
        "title" => $titles[$i], // タイトル
        "date" => $dates[$i], // 日付
        "tag" => $tags[$i], // タグ
        "link" => $links[$i], // リンク
      ];
    }
    $datas = json_encode($params, JSON_UNESCAPED_UNICODE);

    // jsonにしたdatasを返す
    return $datas;
  }

  public function only(Request $request, $id)
  {
    // データの初期化
    $title = ""; // タイトル
    $data = ""; // 内容
    $date = ""; // 日にち
    $tag = ""; // タグ
    $attachments = []; // リンク

    // Goutte\Client
    $client = new Client();

    // ログイン処理
    $login_page = $client->request("GET", (string) env("APP_LOGIN"));
    $login_form = $login_page->filter("form")->form();
    $login_form["id"] = $request->input("id");
    $login_form["pw"] = $request->input("pw");
    $client->submit($login_form);

    // サイトデータ取得
    $crawler = $client->request(
      "GET",
      (string) env("APP_NEWS_FRONT") . $id . (string) env("APP_NEWS_REAR")
    );

    // データの取得
    $title = $crawler->filter(".title")->text(); // タイトル
    $data = $crawler->filter(".news div ~ div")->text(); // 内容
    $date = strstr($crawler->filter(".detail_title01")->text(), " ", true); // 日にち
    $tag = $crawler->filter(".icon01")->text(); // タグ
    $attachments = explode("<a", $crawler->filter(".news")->html());

    // 解体
    array_shift($attachments);
    array_pop($attachments);
    foreach ($attachments as $i => $attachment) {
      $attachments[$i] =
        (string) env("APP_DOMAIN") .
        substr(mb_strstr(substr($attachment, 7), "class", true), 0, -2);
    }

    // 取得したデータをjsonにする
    $params = [
      "title" => $title, // タイトル
      "data" => $data, // 内容
      "date" => $date, // 日にち
      "tag" => $tag, // タグ
      "attachments" => $attachments,
    ];
    $datas = json_encode($params, JSON_UNESCAPED_UNICODE);

    // jsonにしたdatasを返す
    return $datas;
  }

  public function calendar($year, $month, Request $request)
  {
    // データの初期化
    $days = []; // 日にち
    $plans = []; // タイトル

    // Goutte\Client
    $client = new Client();

    // ログイン処理
    $login_page = $client->request("GET", (string) env("APP_LOGIN"));
    $login_form = $login_page->filter("form")->form();
    $login_form["id"] = $request->input("id");
    $login_form["pw"] = $request->input("pw");
    $client->submit($login_form);

    // サイトデータ取得
    $crawler = $client->request(
      "GET",
      (string) env("APP_CALENDAR") .
        "/index.php?c=schedule&cal_yy=" .
        (string) $year .
        "&cal_mm=" .
        (string) $month
    );

    // データの取得
    $crawler
      ->filter("ul.calendar_list01 li")
      ->each(function ($node) use (&$days, &$plans) {
        $days[] = $node->filter(".day")->text(); // 日にち
        // str_replace('amp;', '', (string)env('APP_CALENDAR') . 'url')
        $titles = explode(" ", $node->filter("p + p")->text());
        $links = explode("</a>", explode("<p>", $node->html())[1]);
        array_pop($links);

        foreach ($links as $i => $link) {
          $links[$i] = str_replace(
            "amp;",
            "",
            (string) env("APP_CALENDAR") .
              substr(mb_strstr(substr($link, 12), "style", true), 0, -2)
          );
        }

        $plans[] = [
          "title" => $titles, // タイトル
          "link" => $links, // リンク
        ];
      });

    // 取得したデータをjsonにする
    foreach ($days as $i => $day) {
      if ($plans[$i]["title"][0]) {
        $params[] = [
          "day" => $day, // 日にち
          "plans" => $plans[$i], // タイトル
        ];
      }
    }
    $datas = json_encode($params, JSON_UNESCAPED_UNICODE);

    // jsonにしたdatasを返す
    return $datas;
  }

  public function attendance(Request $request)
  {
    // データの初期化
    $titles = []; // タイトル
    $rates = []; // 率

    // Goutte\Client
    $client = new Client();

    // ログイン処理
    $login_form = $client->request("GET", (string) env("FALCON_LOGIN"));
    $token =
      strstr(
        strstr(
          $client
            ->getHistory()
            ->current()
            ->getUri(),
          "("
        ),
        ")",
        true
      ) . "))";
    $login_form = $login_form->selectButton("ログイン")->form();
    $login_form["txtUserId"] = $request->input("id");
    $login_form["txtPassword"] = $request->input("pw");
    $client->submit($login_form);

    // サイトデータ取得
    $crawler = $client->request(
      "GET",
      (string) env("FALCON_FRONT") . $token . (string) env("FALCON_ATTENDANCE")
    );

    // データの取得
    $crawler->filter("a")->each(function ($node) use (&$titles, &$rates) {
      $titles[] = strstr($node->text(), " ", true); // タイトル
      $rates[] = trim(strstr($node->text(), " ")); // 率
    });

    // 取得したデータをjsonにする
    foreach ($titles as $i => $title) {
      if ($title) {
        $params[] = [
          "title" => $title, // タイトル
          "rate" => $rates[$i], // 率
        ];
      }
    }
    $datas = json_encode($params, JSON_UNESCAPED_UNICODE);

    // jsonにしたdatasを返す
    return $datas;
  }

  public function timetable(Request $request)
  {
    // データの初期化
    $dates = []; // 日にち
    $titles = []; // 日にち

    // Goutte\Client
    $client = new Client();

    // ログイン処理
    $login_form = $client->request("GET", (string) env("FALCON_LOGIN"));
    $token =
      strstr(
        strstr(
          $client
            ->getHistory()
            ->current()
            ->getUri(),
          "("
        ),
        ")",
        true
      ) . "))";
    $login_form = $login_form->selectButton("ログイン")->form();
    $login_form["txtUserId"] = $request->input("id");
    $login_form["txtPassword"] = $request->input("pw");
    $client->submit($login_form);

    // サイトデータ取得
    $crawler = $client->request(
      "GET",
      (string) env("FALCON_FRONT") . $token . (string) env("FALCON_TIMETABLE")
    );

    // データの取得
    $crawler->filter("div")->each(function ($node) use (&$dates) {
      if (strpos($node->text(), "/")) {
        $dates[] = $node->text(); // 日にち
      }
    });

    // 取得したデータをjsonにする
    foreach ($dates as $i => $date) {
      $params[] = [
        "date" => strstr($date, "(", true), // 日にち
        "weekday" => ltrim(trim(strstr(strstr($date, "("), ")", true)), "("),
        "timetable" => [],
      ];
      $datas = array_filter(
        str_replace(
          "限:",
          "",
          preg_split(
            "/[0-9]/",
            preg_replace(
              "/( |　)/",
              "",
              str_replace(
                "行事 ",
                "",
                strstr(
                  strstr(
                    $client->click($crawler->selectLink($date)->link())->text(),
                    "行事"
                  ),
                  "戻る",
                  true
                )
              )
            )
          )
        )
      );
      $params[$i]["timetable"] += $datas;
    }
    $datas = json_encode($params, JSON_UNESCAPED_UNICODE);

    // jsonにしたdatasを返す
    return $datas;
  }
}
