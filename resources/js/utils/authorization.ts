export const hasRole = (role: string, userRoles: string[] = []) =>
    userRoles.includes(role);

/** Company owner (type or role "company") sees full sidebar; others need explicit permission. */
export const isCompanyOwner = (userType?: string | null, roles: string[] = []) =>
    userType === 'company' || roles.includes('company');

export const hasPermission = (
    userPermissions: string[],
    permission: string,
    userType?: string | null,
    roles: string[] = []
) => isCompanyOwner(userType, roles) || userPermissions.includes(permission);