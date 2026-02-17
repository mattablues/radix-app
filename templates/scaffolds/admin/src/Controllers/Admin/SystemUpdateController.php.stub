<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Concerns\FormHelpers;
use App\Models\SystemUpdate;
use App\Requests\Admin\SystemUpdateRequest;
use Radix\Controller\AbstractController;
use Radix\Http\Response;

class SystemUpdateController extends AbstractController
{
    use FormHelpers;

    /**
     * Visa lista över alla systemuppdateringar.
     */
    public function index(): Response
    {
        $rawPage = $this->request->get['page'] ?? 1;
        $page = is_numeric($rawPage) ? (int) $rawPage : 1;

        $rawQ = $this->request->get['q'] ?? '';
        $q = is_string($rawQ) ? trim($rawQ) : '';

        if ($q !== '') {
            $results = SystemUpdate::query()
                ->orderBy('released_at', 'DESC')
                ->search($q, ['version', 'title', 'description'], 10, $page);

            $updates = [
                'data' => $results['data'] ?? [],
                'pagination' => $results['search'] ?? [
                    'term' => $q,
                    'total' => 0,
                    'per_page' => 10,
                    'current_page' => $page,
                    'last_page' => 0,
                    'first_page' => 1,
                ],
            ];
        } else {
            $updates = SystemUpdate::orderBy('released_at', 'DESC')
                ->paginate(10, $page);
        }

        return $this->view('admin.system-update.index', [
            'updates' => $updates,
            'q' => $q,
        ]);
    }

    /**
     * Visa formulär för att skapa en ny uppdatering.
     */
    public function create(): Response
    {
        return $this->view('admin.system-update.create', $this->beginForm());
    }

    /**
     * Lagra en ny systemuppdatering i databasen.
     */
    public function store(): Response
    {
        $this->before();

        $form = new SystemUpdateRequest($this->request);

        if (!$form->validate()) {
            $this->request->session()->set('old', [
                'version' => $form->version(),
                'released_at' => substr($form->releasedAt(), 0, 10),
                'title' => $form->title(),
                'description' => $form->description(),
                'is_major' => $form->isMajor() === 1 ? '1' : '',
            ]);

            return $this->formErrorView('admin.system-update.create', [], $form->errors());
        }

        $update = new SystemUpdate();
        $update->fill([
            'version'     => $form->version(),
            'title'       => $form->title(),
            'description' => $form->description(),
            'released_at' => $form->releasedAt(),
            'is_major'    => $form->isMajor(),
        ]);
        $update->save();

        return $this->formRedirectWithFlash(
            'admin.system-update.index',
            "Systemuppdatering {$update->version} har publicerats framgångsrikt.",
            'info'
        );
    }

    public function edit(string $id): Response
    {
        $update = SystemUpdate::find($id);
        if (!$update) {
            return $this->formRedirect('admin.system-update.index');
        }

        return $this->view('admin.system-update.edit', array_merge(
            ['update' => $update],
            $this->beginForm()
        ));
    }

    /**
     * Uppdatera en befintlig systemuppdatering.
     */
    public function update(string $id): Response
    {
        $this->before();

        $update = SystemUpdate::find($id);

        if (!$update) {
            return $this->formRedirectWithError(
                'admin.system-update.index',
                'Uppdateringen kunde inte hittas.'
            );
        }

        $form = new SystemUpdateRequest($this->request);

        if (!$form->validate()) {
            $this->request->session()->set('old', [
                'version' => $form->version(),
                'released_at' => substr($form->releasedAt(), 0, 10),
                'title' => $form->title(),
                'description' => $form->description(),
                'is_major' => $form->isMajor() === 1 ? '1' : '',
            ]);

            return $this->formErrorView('admin.system-update.edit', [
                'update' => $update,
            ], $form->errors());
        }

        $update->fill([
            'version'     => $form->version(),
            'title'       => $form->title(),
            'description' => $form->description(),
            'released_at' => $form->releasedAt(),
            'is_major'    => $form->isMajor(),
        ]);
        $update->save();

        return $this->formRedirectWithFlash(
            'admin.system-update.index',
            "Systemuppdatering {$update->version} har sparats.",
            'info'
        );
    }

    /**
     * Radera en uppdatering.
     */
    public function delete(string $id): Response
    {
        $this->before();

        $update = SystemUpdate::find($id);

        if ($update) {
            $update->forceDelete();

            return $this->formRedirectWithFlashAndQuery(
                'admin.system-update.index',
                'Uppdateringen har raderats.',
                'info',
                [],
                $this->currentListQuery()
            );
        }

        return $this->formRedirectWithFlashAndQuery(
            'admin.system-update.index',
            'Uppdateringen kunde inte hittas.',
            'error',
            [],
            $this->currentListQuery()
        );
    }
}
