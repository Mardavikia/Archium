SET NAMES utf8mb4;

INSERT INTO roles (name, label) VALUES
  ('admin',  'Amministratore'),
  ('editor', 'Redattore'),
  ('reader', 'Lettore')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO permissions (code, label) VALUES
  ('workspace.create',  'Creare workspace'),
  ('workspace.manage',  'Gestire workspace e membri'),
  ('collection.create', 'Creare raccolte'),
  ('collection.manage', 'Gestire raccolte'),
  ('document.create',   'Creare documenti'),
  ('document.edit',     'Modificare documenti'),
  ('document.delete',   'Eliminare documenti'),
  ('document.publish',  'Creare link pubblici'),
  ('users.manage',      'Gestire utenti')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r
JOIN permissions p ON p.code IN (
  'workspace.create','workspace.manage','collection.create','collection.manage',
  'document.create','document.edit','document.delete','document.publish','users.manage'
)
WHERE r.name = 'admin';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r
JOIN permissions p ON p.code IN (
  'collection.create','document.create','document.edit','document.delete','document.publish'
)
WHERE r.name = 'editor';