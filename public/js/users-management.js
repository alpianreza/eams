(function () {
  document.addEventListener('alpine:init', () => {
    Alpine.data('userManagement', (boot) => ({
      query: '',
      roleFilter: '',
      statusFilter: '',
      roleQuery: '',
      users: Array.isArray(boot?.users) ? boot.users : [],
      roles: Array.isArray(boot?.roles) ? boot.roles : [],

      displayRole(role) {
        return String(role || '').replace(/[_-]+/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()) || '-';
      },

      activeUsersCount() {
        return this.users.filter((user) => user.status === 'active').length;
      },

      filteredUsers() {
        const query = this.query.trim().toLowerCase();
        const roleFilter = this.roleFilter.trim().toLowerCase();
        const statusFilter = this.statusFilter.trim().toLowerCase();

        return this.users.filter((user) => {
          const haystack = [
            user.name,
            user.username,
            user.wa_number,
            user.role,
            user.permission,
            user.status,
          ].join(' ').toLowerCase();

          const matchesQuery = !query || haystack.includes(query);
          const matchesRole = !roleFilter || String(user.role || '').toLowerCase() === roleFilter;
          const matchesStatus = !statusFilter || String(user.status || '').toLowerCase() === statusFilter;

          return matchesQuery && matchesRole && matchesStatus;
        });
      },

      filteredRoles() {
        const query = this.roleQuery.trim().toLowerCase();

        return this.roles.filter((role) => {
          if (!query) return true;
          return String(role.name || '').toLowerCase().includes(query) || String(role.label || '').toLowerCase().includes(query);
        });
      },
    }));
  });
})();
