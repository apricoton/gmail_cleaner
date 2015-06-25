# Gmailから特定のメールを一括削除
溜まりすぎたメールを削除します。

## 注意
**本スクリプトはとても危険な処理をしています。スクリプトの内容をご確認の上、自己責任でご利用ください。**

## 準備
1. 本リポジトリをclone

        git clone https://github.com/apricoton/gmail_cleaner.git
1. https://console.developers.google.com/ にアクセスし既存のプロジェクトを選択、または新規でプロジェクトを作成
1. 「API と認証」から、「API」を選択し、Gmail APIを有効化 (gmailと検索すれば出てきます)
1. 「API と認証」から、「同意画面」を選択し、サービス名を適当につける
1. 「API と認証」から、「認証情報」を選択し
    * 「OAuth」の「新しいクライアント ID を作成」をクリック
    * 「アプリケーションの種類」は「インストールされているアプリケーション」、「インストールされているアプリケーションの種類」は「その他」を選択し、「クライアント ID を作成」をクリック
    * 「JSON をダウンロード」でJSONファイルを「client_secret.json」として、アプリケーションと同じディレクトリにダウンロードする
1. composer updateする

        php composer.phar update

## 実行する
```bash
php delete.php あなたのメールアドレス 検索クエリ（Gmail 検索形式 スペース区切り）
```
* 初回のみ認証URLが出力されるのでアクセスし、認証コードを取得＆コピーして端末にペーストする
### サンプル
```bash
php delete.php me "Cron Daemon" before:2014/12/31
```

