USE proyecto7mo;

-- Password for all demo users: password
SET @demo_password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

INSERT INTO users (id, name, username, email, password_hash, description, profile_image, is_public, role) VALUES
(1, 'Admin NexoHub', 'admin', 'admin@nexohub.local', @demo_password, 'Cuenta administradora para pruebas.', NULL, 1, 'admin'),
(2, 'Carlos Perez', 'carlosp', 'carlos@nexohub.local', @demo_password, 'Fan del cine de aventura y accion.', NULL, 1, 'user'),
(3, 'Ana Gomez', 'anagomez', 'ana@nexohub.local', @demo_password, 'Resenas de drama, romance y comedia.', NULL, 1, 'user'),
(4, 'Maria Lopez', 'marial', 'maria@nexohub.local', @demo_password, 'Me gustan las historias con misterio.', NULL, 1, 'user'),
(5, 'Super Admin', 'superadmin', 'superadmin@nexohub.local', @demo_password, 'Cuenta superadmin para administracion completa.', NULL, 1, 'superadmin')
ON DUPLICATE KEY UPDATE
name = VALUES(name),
username = VALUES(username),
description = VALUES(description),
is_public = VALUES(is_public),
role = VALUES(role);

INSERT INTO reviewers (id, user_id, name, reputation) VALUES
(1, 1, 'Admin NexoHub', 40),
(2, 2, 'Carlos Perez', 120),
(3, 3, 'Ana Gomez', 95),
(4, 4, 'Maria Lopez', 80),
(5, 5, 'Super Admin', 200)
ON DUPLICATE KEY UPDATE
user_id = VALUES(user_id),
name = VALUES(name),
reputation = VALUES(reputation);
