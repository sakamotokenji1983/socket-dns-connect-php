<?php
/*
 * Copyright (c) 2024 SAKAMOTO Kenji
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

require_once __DIR__ . "/../vendor/autoload.php";

use Dotenv\Dotenv;

try {

    // .envのロード
    $dotenv = Dotenv::createImmutable(__DIR__ . "/..");
    $dotenv->load();

    // 検証するドメイン名
    $hostname = $_ENV["HOST_NAME"];

    // DNSサーバのIPアドレス
    $dns_server = $_ENV["DNS_SERVER"]; // GoogleのDNSサーバ

    /**
     * @var int $headerId ヘッダー部 ID
     * 16ビットのIDで、任意の種類の問い合わせを生成するプログラムによって割り当てられる。
     * このIDは対応する応答にコピーされ、リクエスト発行者が応答と発行済みの問い合わせを一致させるために使用できる。
     */
    $headerId = 0b1010101010101010;

    /**
     * @var int $headerQr ヘッダー部 QR
     * 1ビットのフィールドで、このメッセージが問い合わせ(0)か応答(1)かを指定する。
     */
    $headerQr = 0b0000000000000000;

    /**
     * @var int $headerOpCode ヘッダー部 OPCODE
     * 4ビットのフィールドで、このメッセージの問い合わせ種別を
     * 指定する。この値は問い合わせ生成者によって設定され、
     * 応答にコピーされる。採りうる値は以下の通りである。
     * 0    標準問い合わせ(QUERY)。
     * 1    逆問い合わせ(IQUERY)。
     * 2    サーバーステータスのリクエスト(STATUS)。
     * 3-15 将来の利用のために予約。
     */
    $headerOpCode = 0b0000000000000000;

    /**
     * @var int $headerAa ヘッダー部 AA
     * 権威を持つ回答(Authoritative Answer):このビットは応答で
     * 有効なものであり、応答したネームサーバーが問い合わせ部の
     * ドメイン名の権威であるかを指定する。
     * 回答部の内容は、別名に起因して複数の所有者名を持つ場合が
     * あることに注意せよ。
     * AAビットは、問い合わせ名に一致する名前か、回答部の最初に
     * 現れる所有者名に対応する。
     */
    $headerAa = 0b0000000000000000;

    /**
     * @var int $headerTc ヘッダー部 IC
     * 切り詰め(TrunCation): このメッセージが転送チャネルで許容
     * されるよりも長かったために切り詰められたかを指定する。
     */
    $headerTc = 0b0000000000000000;

    /**
     * @var int $headerRd ヘッダー部 RD
     * 再帰要求(Recursion Desired): このビットは問い合わせで設定
     * することができ、応答にもコピーされる。RDが設定されている
     * 場合、ネームサーバーに再帰的に問い合わせを継続せよという
     * 指示になる。再帰問い合わせのサポートは任意である。
     */
    $headerRd = 0b0000000100000000;

    /**
     * @var int $headerRa ヘッダー部 RA
     * 再帰可能(Recursion Available): このビットは応答で設定または
     * クリアされるもので、そのネームサーバーにおいて再帰問い合わせが
     * 利用可能かどうかを提示する。
     */
    $headerRa = 0b0000000000000000;

    /**
     * @var int $headerZ ヘッダー部 Z
     * 将来の利用のために予約済みとなっている。すべての問い合わせ、
     * 応答で、このフィールドはすべてゼロでなければならない。
     */
    $headerZ = 0b0000000000000000;

    /**
     * @var int $headerRcode ヘッダー部 RCODE
     * 応答コード(Response code): この4ビットフィールドは、応答の
     * 一部として設定される。設定される値は以下に示すように解釈される。
     * 0    ... エラー無し
     * 1    ... フォーマットエラー: ネームサーバーは問い合わせを解釈できなかった。
     * 2    ... サーバー障害: ネームサーバーは、サーバー側の問題でこの問い合わせを処理できなかった。
     * 3    ... ドメイン名不在: 権威ネームサーバーからの応答でのみ意味を持つ。このコードは、
     *          問い合わせで参照されたドメイン名が存在しなかったことを提示する。
     * 4    ... 未実装: ネームサーバーはリクエストされた種別の問い合わせをサポートしていない。
     * 5    ... 問い合わせ拒否: ネームサーバーは、ポリシーによる理由で指定された処理の実行を拒否する。
     *          例えば、ネームサーバーは特定のリクエスト発行者には情報を提供したくないと望むかも
     *          しれない。あるいはネームサーバーは特定のデータに関する特定の処理(例えばゾーン転送)を実行したくないと望むかもしれない。
     * 6-15 ... 将来の利用のために予約｡
     */
    $headerRcode = 0b0000000000000000;

    /**
     * @var int $headerQdCount ヘッダー部 QDCOUNT
     * 符号無し16ビット整数で、問い合わせ部のエントリー数を指定する。
     */
    $headerQdCount = 0b0000000000000001;

    /**
     * @var int $headerAnCount ヘッダー部 ANCOUNT
     * 符号無し16ビット整数で、回答部のリソースレコード数を指定する。
     */
    $headerAnCount = 0b0000000000000000;

    /**
     * @var int $headerNsCount ヘッダー部 NSCOUNT
     * 符号無し16ビット整数で、権威部のネームサーバーリソースレコード数を指定する。
     */
    $headerNsCount = 0b0000000000000000;

    /**
     * @var int $headerArCount ヘッダー部 ARCOUNT
     * 符号無し16ビット整数で、付加情報部のリソースレコード数を指定する
     */
    $headerArCount = 0b0000000000000000;

    $header = "";
    // ヘッダー部の1段目としてバイナリデータにパックする
    $header .= pack("n", $headerId);
    // ヘッダー部の2段目としてバイナリデータにパックする
    $header .= pack(
        "n",
        $headerQr |
        $headerOpCode |
        $headerAa |
        $headerTc |
        $headerRd |
        $headerRa |
        $headerZ |
        $headerRcode
    );
    // ヘッダー部の3段目としてバイナリデータにパックする
    $header .= pack("n", $headerQdCount);
    // ヘッダー部の4段目としてバイナリデータにパックする
    $header .= pack("n", $headerAnCount);
    // ヘッダー部の5段目としてバイナリデータにパックする
    $header .= pack("n", $headerNsCount);
    // ヘッダー部の6段目としてバイナリデータにパックする
    $header .= pack("n", $headerArCount);

    // ヘッダーを16進数表現の文字列に変換する
    $headerHex = bin2hex($header);
    // 大文字に変換する
    $headerHex = strtoupper($headerHex);
    echo $headerHex . PHP_EOL;

    /**
     * @var string $questionQname QNAME
     * ラベルの並びとして表現されるドメイン名で、各ラベルは、
     * ラベル長オクテットと、その数だけオクテットが続くもので構成
     * される。ドメイン名は、ルートのヌルラベルに関するゼロが指定
     * されたラベル長オクテットで終端される。このフィールドの
     * オクテット数が奇数であってもよいことに注意せよ。パディングは
     * 使用されない。
     */
    $questionQname = ""; // 変数を初期化する
    $labels = explode(".", $hostname); // ドメイン名をドットで分割する
    foreach ($labels as $label) {
        $length = strlen($label); // ラベルの長さを取得する
        $binaryLength = pack("C", $length); // ラベルの長さをオクテットに変換する
        $questionQname .= $binaryLength . (binary) $label; // ラベル長オクテット + ラベルを結合してセットする
    }
    $questionQname .= pack("C", 0); // 最後にヌルラベルをセットする

    // $questionQnameの確認
    // ヘッダーを16進数表現の文字列に変換する
    $headerHex = bin2hex($questionQname);
    // 大文字に変換する
    $headerHex = strtoupper($headerHex);
    echo sprintf("\$questionQname: %s\n", $headerHex);

    /**
     * @var int $questionQtype QTYPE
     * 2オクテットのコードで、問い合わせのタイプを指定する。
     * このフィールドの値は、TYPEフィールドで有効なすべてのコードに
     * 加えて、二つ以上のタイプのRRに一致できるより一般的なコードを
     * 幾つか含む。
     */
    $questionQtype = 0b0000000000000001;

    /**
     * @var int $questionQclass QCLASS
     * 2オクテットのコードで、問い合わせのタイプを指定する。
     * このフィールドの値は、TYPEフィールドで有効なすべてのコードに
     * 加えて、二つ以上のタイプのRRに一致できるより一般的なコードを
     * 幾つか含む。
     */
    $questionQclass = 0b0000000000000001;

    /**
     * @var string $question 問い合わせ部
     */
    $question = "";
    $question .= $questionQname; // QNAME
    $question .= pack("n", $questionQtype); // QTYPE
    $question .= pack("n", $questionQclass); // QCLASS

    // DNSのクエリメッセージを作成
    $query  = "";
    $query .= $header; // ヘッダー部
    $query .= $question; // 問い合わせ部

    // UDPソケットを作成
    if (!$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP)) {
        throw new Exception(sprintf(
            "socket_create() failed: reason: %s\n",
            socket_strerror(socket_last_error())
        ));
    }

    // DNSサーバにメッセージを送信
    if (!socket_sendto($sock, $query, strlen($query), 0, $dns_server, 53)) {
        throw new Exception(sprintf(
            "socket_sendto() failed: reason: %s\n",
            socket_strerror(socket_last_error())
        ));
    }

    // 応答を受信
    $port = null;
    if (!socket_recvfrom($sock, $response, 512, 0, $address, $port)) {
        throw new Exception(sprintf(
            "socket_recvfrom() failed: reason: %s\n",
            socket_strerror(socket_last_error())
        ));
    }

    // ソケットを閉じる
    socket_close($sock);

    // 受信したデータのサイズを確認する
    echo sprintf("response size: %d Byte\n", strlen($response));

    // 受信したデータを表示する
    echo sprintf("response:\n%s\n", strtoupper(bin2hex($response)));

    parse_dns_response($response);

} catch (Throwable $e) {

    echo sprintf(
        "%s:%s %s\n",
        $e->getFile(),
        $e->getLine(),
        $e->getMessage()
    );

}

function parse_dns_response($response) {

    $transaction_id = bin2hex(substr($response, 0, 2));
    $flags = bin2hex(substr($response, 2, 2));
    $questions = hexdec(bin2hex(substr($response, 4, 2)));
    $answer_rrs = hexdec(bin2hex(substr($response, 6, 2)));
    $authority_rrs = hexdec(bin2hex(substr($response, 8, 2)));
    $additional_rrs = hexdec(bin2hex(substr($response, 10, 2)));

    echo "Transaction ID: $transaction_id\n";
    echo "Flags: $flags\n";
    echo "Questions: $questions\n";
    echo "Answer RRs: $answer_rrs\n";
    echo "Authority RRs: $authority_rrs\n";
    echo "Additional RRs: $additional_rrs\n";

    // 質問セクションの解析
    $offset = 12;
    for ($i = 0; $i < $questions; $i++) {
        $hostname = '';
        while (true) {
            $len = ord($response[$offset]);
            if ($len == 0) {
                $offset++;
                break;
            }
            $hostname .= substr($response, $offset + 1, $len) . '.';
            $offset += $len + 1;
        }
        $type = bin2hex(substr($response, $offset, 2));
        $class = bin2hex(substr($response, $offset + 2, 2));
        $offset += 4;

        echo "Question: $hostname\n";
        echo "Type: $type\n";
        echo "Class: $class\n";
    }

    // 応答セクションの解析
    for ($i = 0; $i < $answer_rrs; $i++) {
        $hostname = '';
        $len = ord($response[$offset]);
        if ($len == 0xc0) {
            $ptr = ord($response[$offset + 1]);
            $hostname = "Pointer to offset $ptr";
            $offset += 2;
        } else {
            while (true) {
                $len = ord($response[$offset]);
                if ($len == 0) {
                    $offset++;
                    break;
                }
                $hostname .= substr($response, $offset + 1, $len) . '.';
                $offset += $len + 1;
            }
        }
        $type = bin2hex(substr($response, $offset, 2));
        $class = bin2hex(substr($response, $offset + 2, 2));
        $ttl = hexdec(bin2hex(substr($response, $offset + 4, 4)));
        $data_len = hexdec(bin2hex(substr($response, $offset + 8, 2)));
        $data = substr($response, $offset + 10, $data_len);
        $offset += 10 + $data_len;

        if ($type == '0001') { // Type A
            $data = implode('.', array_map('ord', str_split($data)));
        }

        echo "Answer: $hostname\n";
        echo "Type: $type\n";
        echo "Class: $class\n";
        echo "TTL: $ttl\n";
        echo "Data length: $data_len\n";
        echo "Data: $data\n";
    }
}
