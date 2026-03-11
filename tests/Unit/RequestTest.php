<?php
/**
 * Unit Tests — Request
 */

require_once __DIR__ . '/../BaseTestCase.php';

class RequestTest extends BaseTestCase {
    public function testCreateFromArrays(): void {
        $request = Request::create(
            ['REQUEST_METHOD' => 'POST', 'HTTP_HOST' => 'example.com', 'REMOTE_ADDR' => '1.2.3.4'],
            ['page' => 'sales', 'action' => 'create'],
            ['name' => 'Test Sale']
        );

        $this->assertEquals('POST', $request->method());
        $this->assertEquals('sales', $request->query('page'));
        $this->assertEquals('create', $request->query('action'));
        $this->assertEquals('Test Sale', $request->input('name'));
        $this->assertEquals('1.2.3.4', $request->ip());
    }

    public function testQueryDefaultValue(): void {
        $request = Request::create(
            ['REQUEST_METHOD' => 'GET'],
            [],
            []
        );

        $this->assertEquals('dashboard', $request->query('page', 'dashboard'));
        $this->assertNull($request->query('nonexistent'));
    }

    public function testPageAndActionHelpers(): void {
        $request = Request::create(
            ['REQUEST_METHOD' => 'GET'],
            ['page' => 'products', 'action' => 'edit'],
            []
        );

        $this->assertEquals('products', $request->page());
        $this->assertEquals('edit', $request->action());
    }

    public function testMethodDetection(): void {
        $get = Request::create(['REQUEST_METHOD' => 'GET'], [], []);
        $post = Request::create(['REQUEST_METHOD' => 'POST'], [], []);

        $this->assertTrue($get->isGet());
        $this->assertFalse($get->isPost());
        $this->assertTrue($post->isPost());
        $this->assertFalse($post->isGet());
    }

    public function testIsAjax(): void {
        $ajax = Request::create(
            ['REQUEST_METHOD' => 'GET', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            [],
            []
        );
        $normal = Request::create(['REQUEST_METHOD' => 'GET'], [], []);

        $this->assertTrue($ajax->isAjax());
        $this->assertFalse($normal->isAjax());
    }

    public function testInputReturnsDefault(): void {
        $request = Request::create(['REQUEST_METHOD' => 'POST'], [], []);
        $this->assertEquals('N/A', $request->input('missing', 'N/A'));
    }

    public function testSafeQuerySanitizesHtml(): void {
        $request = Request::create(
            ['REQUEST_METHOD' => 'GET'],
            ['search' => '<script>alert("xss")</script>', 'page' => 'sales'],
            []
        );

        $safe = $request->safeQuery();
        $this->assertStringNotContainsString('<script>', $safe['search']);
        $this->assertEquals('sales', $safe['page']);
    }

    public function testAllReturnsPostData(): void {
        $request = Request::create(
            ['REQUEST_METHOD' => 'POST'],
            [],
            ['field1' => 'value1', 'field2' => 'value2']
        );

        $all = $request->all();
        $this->assertEquals('value1', $all['field1']);
        $this->assertEquals('value2', $all['field2']);
    }
}
