SET NAMES utf8mb4;

ALTER TABLE workspace_members ADD INDEX idx_wm_workspace_role (workspace_id, role);
ALTER TABLE collection_members ADD INDEX idx_cm_user_collection (user_id, collection_id);
ALTER TABLE document_permissions ADD INDEX idx_dp_user_document (user_id, document_id);
ALTER TABLE public_links ADD UNIQUE INDEX uq_pl_token_hash (token_hash);
ALTER TABLE public_links ADD INDEX idx_pl_expires_revoked (expires_at, revoked_at);
ALTER TABLE users ADD INDEX idx_users_status (status);