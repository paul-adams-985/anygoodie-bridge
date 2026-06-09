<?php

test('the application redirects from the homepage', function (): void {
    $response = $this->get('/');

    $response->assertRedirect();
});
