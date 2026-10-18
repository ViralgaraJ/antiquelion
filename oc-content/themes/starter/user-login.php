<?php
  // Sign-in shares the auth template with registration; the mode picks the form.
  View::newInstance()->_exportVariableToView('al_auth_mode', 'login');
  require __DIR__ . '/user-register.php';
