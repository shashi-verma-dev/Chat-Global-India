<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Public channel for the global chat
Broadcast::channel('global-chat', function () {
    return true; // Anyone can listen
});
