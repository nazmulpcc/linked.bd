<?php

it('shows the api documentation', function () {
    $response = $this->get('/docs');

    $response->assertOk();
});

it('serves the openapi specification', function () {
    $response = $this->get('/docs.openapi');

    $response->assertOk();
});
