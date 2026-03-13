import SearchSystemEvents from './search-system-events';
import SearchSystemUpdates from './search-system-updates';
import SearchUsers from './search-users';
import SearchDeletedUsers from './search-deleted-users';

function getPageId() {
  return document.querySelector('[data-page-id]')?.getAttribute('data-page-id')
    || (document.body ? document.body.id : null);
}

function getInitialState() {
  const params = new URLSearchParams(window.location.search);

  return {
    initialTerm: (params.get('q') || '').trim(),
    initialPage: Number.parseInt(params.get('page') || '1', 10) || 1,
  };
}

function elementExists(id) {
  return !!document.getElementById(id);
}

function getEndpointFromForm(formId, fallback) {
  const form = document.getElementById(formId);
  const endpoint = form ? (form.getAttribute('data-search-endpoint') || '') : '';

  return endpoint || fallback;
}

function initTableSearch(key, requiredIds, createInstance) {
  const ok = requiredIds.every(elementExists);

  if (!ok) {
    return null;
  }

  window.__tableSearchInstances = window.__tableSearchInstances || {};

  const previous = window.__tableSearchInstances[key];

  if (previous && typeof previous.destroy === 'function') {
    previous.destroy();
  }

  const instance = createInstance();
  window.__tableSearchInstances[key] = instance;

  return instance;
}

export function initTableSearches() {
  const pageId = getPageId();

  if (pageId === 'admin-events-index') {
    const { initialTerm, initialPage } = getInitialState();
    const endpoint = getEndpointFromForm('system-events-search-form', '/api/v1/search/system-events');

    initTableSearch(
      'systemEvents',
      ['system-events-search-form', 'system-events-search', 'system-events-tbody'],
      () => new SearchSystemEvents({
        formId: 'system-events-search-form',
        clearBtnId: 'system-events-clear',
        inputId: 'system-events-search',
        tbodyId: 'system-events-tbody',
        pagerId: 'system-events-pager',
        summaryId: 'system-events-search-summary',
        summarySingular: 'logg',
        summaryPlural: 'loggar',
        summarySuffix: 'totalt',
        endpoint,
        routeBase: '/admin/events',
        initialTerm,
        initialPage,
      }),
    );
  }

  if (pageId === 'admin-updates-index') {
    const { initialTerm, initialPage } = getInitialState();
    const endpoint = getEndpointFromForm('system-updates-search-form', '/api/v1/search/system-updates');

    initTableSearch(
      'systemUpdates',
      ['system-updates-search-form', 'system-updates-search', 'system-updates-tbody'],
      () => new SearchSystemUpdates({
        formId: 'system-updates-search-form',
        clearBtnId: 'system-updates-clear',
        inputId: 'system-updates-search',
        tbodyId: 'system-updates-tbody',
        pagerId: 'system-updates-pager',
        summaryId: 'system-updates-search-summary',
        summarySingular: 'logg',
        summaryPlural: 'loggar',
        summarySuffix: 'totalt',
        endpoint,
        routeBase: '/admin/updates',
        initialTerm,
        initialPage,
      }),
    );
  }

  if (pageId === 'admin-users-index') {
    const { initialTerm, initialPage } = getInitialState();
    const endpoint = getEndpointFromForm('users-search-form', '/api/v1/search/users');

    initTableSearch(
      'users',
      ['users-search-form', 'users-search', 'users-tbody'],
      () => new SearchUsers({
        formId: 'users-search-form',
        clearBtnId: 'users-clear',
        inputId: 'users-search',
        tbodyId: 'users-tbody',
        pagerId: 'users-pager',
        summaryId: 'users-search-summary',
        summarySingular: 'konto',
        summaryPlural: 'konton',
        summarySuffix: 'totalt',
        endpoint,
        routeBase: '/admin/users',
        initialTerm,
        initialPage,
      }),
    );
  }

  if (pageId === 'admin-user-closed') {
    const { initialTerm, initialPage } = getInitialState();
    const endpoint = getEndpointFromForm('deleted-users-search-form', '/api/v1/search/deleted-users');

    initTableSearch(
      'deletedUsers',
      ['deleted-users-search-form', 'deleted-users-search', 'deleted-users-tbody'],
      () => new SearchDeletedUsers({
        formId: 'deleted-users-search-form',
        clearBtnId: 'deleted-users-clear',
        inputId: 'deleted-users-search',
        tbodyId: 'deleted-users-tbody',
        pagerId: 'deleted-users-pager',
        summaryId: 'deleted-users-search-summary',
        summarySingular: 'stängt konto',
        summaryPlural: 'stängda konton',
        summarySuffix: 'totalt',
        endpoint,
        routeBase: '/admin/users/closed',
        initialTerm,
        initialPage,
      }),
    );
  }
}
