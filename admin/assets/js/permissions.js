(function ($) {
    'use strict';

    const $page = $('#permissionsPage');
    if (!$page.length) {
        return;
    }

    const endpoint = $page.data('endpoint');
    const csrf = $('meta[name="csrf-token"]').attr('content') || '';
    let selectedRoleId = Number($('.role-card.active').data('role-id') || 0);

    function postAction(payload) {
        return $.ajax({
            url: endpoint,
            method: 'POST',
            dataType: 'json',
            data: payload,
        });
    }

    function updateRolePanel(data) {
        const role = data.role || {};
        const permissions = data.permissions || [];

        $('#roleDetailsTitle').text(role.display_name ? role.display_name + ' (' + role.role_name + ')' : 'Role details');

        const $list = $('#rolePermissionList');
        $list.empty();

        if (!permissions.length) {
            $('#rolePermissionEmpty').show();
        } else {
            $('#rolePermissionEmpty').hide();
            permissions.forEach(function (item) {
                const tag = $('<span/>', {
                    class: 'permission-tag',
                    text: item.permission_key,
                    title: item.label_ar || item.label_en || item.permission_key,
                });
                $list.append(tag);
            });
        }

        $('#edit_role_id').val(role.role_id || '');
        $('#edit_role_name').val(role.role_name || '');
        $('#edit_role_display').val(role.display_name || '');
        $('#edit_role_description').val(role.description || '');
    }

    function loadRoleDetails(roleId) {
        if (!roleId) {
            return;
        }

        postAction({
            action: 'get_role_details',
            role_id: roleId,
            csrf_token: csrf,
        })
            .done(function (response) {
                if (!response.success) {
                    showToast('error', response.message || 'Failed to load role details.');
                    return;
                }

                updateRolePanel(response.data);
            })
            .fail(function () {
                showToast('error', 'Failed to load role details.');
            });
    }

    $(document).on('click', '.role-card', function () {
        $('.role-card').removeClass('active');
        $(this).addClass('active');
        selectedRoleId = Number($(this).data('role-id') || 0);
        loadRoleDetails(selectedRoleId);
    });

    $(document).on('change', '.permission-toggle', function () {
        const $toggle = $(this);
        const roleId = Number($toggle.data('role-id'));
        const permissionId = Number($toggle.data('permission-id'));
        const allowed = $toggle.is(':checked') ? 1 : 0;

        postAction({
            action: 'toggle_permission',
            role_id: roleId,
            permission_id: permissionId,
            allowed: allowed,
            csrf_token: csrf,
        })
            .done(function (response) {
                if (!response.success) {
                    showToast('error', response.message || 'Permission update failed.');
                    $toggle.prop('checked', !allowed);
                    return;
                }

                const $roleCard = $('.role-card[data-role-id="' + roleId + '"]');
                const summaryText = 'Assigned permissions: ' + (response.permission_count || 0);
                $roleCard.find('p').html(summaryText.replace(String(response.permission_count), '<strong>' + (response.permission_count || 0) + '</strong>'));

                if (selectedRoleId === roleId) {
                    loadRoleDetails(roleId);
                }

                showToast('success', 'Permission updated.');
            })
            .fail(function () {
                showToast('error', 'Permission update request failed.');
                $toggle.prop('checked', !allowed);
            });
    });

    $('#createRoleForm').on('submit', function (event) {
        event.preventDefault();

        postAction($(this).serialize())
            .done(function (response) {
                if (!response.success) {
                    showToast('error', response.message || 'Role creation failed.');
                    return;
                }

                showToast('success', response.message || 'Role created.');
                closeModal('createRoleModal');
                setTimeout(function () {
                    window.location.reload();
                }, 350);
            })
            .fail(function () {
                showToast('error', 'Role creation request failed.');
            });
    });

    $('#openEditRole').on('click', function () {
        if (!selectedRoleId) {
            showToast('error', 'Please select a role first.');
            return;
        }

        loadRoleDetails(selectedRoleId);
        openModal('editRoleModal');
    });

    $('#editRoleForm').on('submit', function (event) {
        event.preventDefault();

        postAction($(this).serialize())
            .done(function (response) {
                if (!response.success) {
                    showToast('error', response.message || 'Role update failed.');
                    return;
                }

                showToast('success', response.message || 'Role updated.');
                closeModal('editRoleModal');
                setTimeout(function () {
                    window.location.reload();
                }, 350);
            })
            .fail(function () {
                showToast('error', 'Role update request failed.');
            });
    });

    if (selectedRoleId) {
        loadRoleDetails(selectedRoleId);
    }
})(jQuery);
