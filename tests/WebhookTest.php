<?php

class WebhookTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->runBackground('php -S localhost:8084 ' . escapeshellarg(__DIR__ . '/system_a.php'));
        $this->runBackground('php -S localhost:8085 ' . escapeshellarg(__DIR__ . '/system_b.php'));
    }

    protected function tearDown(): void
    {
        @unlink(__DIR__ . '/system_a.txt');
        @unlink(__DIR__ . '/system_b.txt');
    }

    public function test_Webhook_should_propagate_info_to_systems()
    {
        $data = <<<JSON
[
    {
        "id": 1,
        "name": "My Product",
        "prices": {
            "was": "123.45",
            "now": "99.00"
        },
        "description": "This is a great product",
        "images": [
            "https://my-website.com/image1.jpg"
        ],
        "meta": {
            "created_at": "2020-12-10 08:53:12",
            "updated_at": "2020-12-10 18:42:57"
        }
    }
]
JSON;
        $expectedA = '{"id":1,"price":99,"image":"https:\/\/my-website.com\/image1.jpg"}';
        $expectedB = 'ids=1';

        $this->assertEquals(200 , $this->post('nla/klevu/product', $data), 'Endpoint should return HTTP 200');
        sleep(30);
        $this->assertSystemResponse($expectedA, 'system_a');
        $this->assertSystemResponse($expectedB, 'system_b');
    }

    protected function assertSystemResponse(string $expectedResponse, string $systemName)
    {
        $fileName = __DIR__ . '/' . $systemName . '.txt';
        $this->assertFileExists($fileName, "No response from $systemName");
        $response = file_get_contents($fileName);
        $this->assertEquals($expectedResponse, $response);
    }

    protected function runBackground($cmd)
    {
        if (substr(php_uname(), 0, 7) == "Windows") {
            pclose(popen("start /B $cmd", "r"));
        } else {
            exec($cmd . " > /dev/null &");
        }
    }

    protected function post(string $url, string $data, int $port = 80, string $type = 'text/json')
    {
        $ch = curl_init();
        $headers = [
            'Content-Type: ' . $type
        ];
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_PORT, $port);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_exec($ch);

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $status;
    }
}
