<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-people"></i> <?= t('admin.users.title') ?></span>
        <span class="badge bg-secondary"><?= count($users) ?></span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th><?= t('admin.users.col.id') ?></th>
                        <th><?= t('admin.users.col.username') ?></th>
                        <th><?= t('admin.users.col.role') ?></th>
                        <th><?= t('admin.users.col.created') ?></th>
                        <th><?= t('common.actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>#<?= (int)$user['id'] ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td>
                                <?php
                                $roleBadge = [
                                    'admin'    => 'danger',
                                    'user'     => 'success',
                                    'inactive' => 'secondary',
                                ][$user['role']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $roleBadge ?>"><?= t('admin.users.role.' . $user['role']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($user['created_at']) ?></td>
                            <td>
                                <?php if ((int)$user['id'] === AuthHelper::getUserId()): ?>
                                    <span class="text-muted small">—</span>
                                <?php else: ?>
                                    <form method="POST" action="<?= BASE_URL ?>/index.php?page=admin&action=updateRole" class="d-inline-flex gap-1">
                                        <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                                        <select name="role" class="form-select form-select-sm" style="width:auto">
                                            <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>><?= t('admin.users.role.user') ?></option>
                                            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>><?= t('admin.users.role.admin') ?></option>
                                            <option value="inactive" <?= $user['role'] === 'inactive' ? 'selected' : '' ?>><?= t('admin.users.role.inactive') ?></option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-outline-primary"><?= t('common.save') ?></button>
                                    </form>
                                    <form method="POST" action="<?= BASE_URL ?>/index.php?page=admin&action=delete" class="d-inline"
                                          data-confirm-submit="<?= t('common.confirm_delete') ?>">
                                        <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
