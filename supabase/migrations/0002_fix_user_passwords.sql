-- Actualizar password hashes de usuarios demo generados con PHP password_hash('12345678', PASSWORD_DEFAULT)
UPDATE public.users SET password_hash = '$2y$10$0WYIRmxaTwlGRA4jeoAsHuR9CWp4WkhCyvF7gYuVJbVEK2XDTQftm' WHERE id IN (1, 2, 3);
