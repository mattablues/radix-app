import SearchSystemEvents from './search-system-events';
import SearchSystemUpdates from './search-system-updates';
import SearchUsers from './search-users';
import SearchDeletedUsers from './search-deleted-users';
import SearchBlockedEmails from './search-blocked-emails';

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

function getRequiredFormValue(formId, key) {
  const form = document.getElementById(formId);

  if (!form) {
    return '';
  }

  return form.dataset?.[key] || '';
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
    const endpoint = getRequiredFormValue('system-events-search-form', 'searchEndpoint');
    const routeBase = getRequiredFormValue('system-events-search-form', 'routeBase');

    if (!endpoint || !routeBase) {
      return;
    }

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
        routeBase,
        initialTerm,
        initialPage,
      }),
    );
  }

  if (pageId === 'admin-updates-index') {
    const { initialTerm, initialPage } = getInitialState();
    const endpoint = getRequiredFormValue('system-updates-search-form', 'searchEndpoint');
    const routeBase = getRequiredFormValue('system-updates-search-form', 'routeBase');

    if (!endpoint || !routeBase) {
      return;
    }

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
        routeBase,
        initialTerm,
        initialPage,
      }),
    );
  }

  if (pageId === 'admin-users-index') {
    const { initialTerm, initialPage } = getInitialState();
    const endpoint = getRequiredFormValue('users-search-form', 'searchEndpoint');
    const routeBase = getRequiredFormValue('users-search-form', 'routeBase');

    if (!endpoint || !routeBase) {
      return;
    }

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
        routeBase,
        initialTerm,
        initialPage,
      }),
    );
  }

  if (pageId === 'admin-user-closed') {
    const { initialTerm, initialPage } = getInitialState();
    const endpoint = getRequiredFormValue('deleted-users-search-form', 'searchEndpoint');
    const routeBase = getRequiredFormValue('deleted-users-search-form', 'routeBase');

    if (!endpoint || !routeBase) {
      return;
    }

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
        routeBase,
        initialTerm,
        initialPage,
      }),
    );
  }

  if (pageId === 'admin-blocked-email-index') {
    const { initialTerm, initialPage } = getInitialState();
    const endpoint = getRequiredFormValue('blocked-emails-search-form', 'searchEndpoint');
    const routeBase = getRequiredFormValue('blocked-emails-search-form', 'routeBase');

    if (!endpoint || !routeBase) {
      return;
    }

    initTableSearch(
      'blockedEmails',
      ['blocked-emails-search-form', 'blocked-emails-search', 'blocked-emails-tbody'],
      () => new SearchBlockedEmails({
        formId: 'blocked-emails-search-form',
        clearBtnId: 'blocked-emails-clear',
        inputId: 'blocked-emails-search',
        tbodyId: 'blocked-emails-tbody',
        pagerId: 'blocked-emails-pager',
        summaryId: 'blocked-emails-search-summary',
        summarySingular: 'blockerad adress',
        summaryPlural: 'blockerade adresser',
        summarySuffix: 'totalt',
        endpoint,
        routeBase,
        initialTerm,
        initialPage,
      }),
    );
  }
}
