<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\SystemUpdate;
use Radix\Controller\AbstractController;
use Radix\Http\RedirectResponse;
use Radix\Http\Response;
use Radix\Support\Validator;

class SystemUpdateController extends AbstractController
{
    /**
     * Visa lista över alla systemuppdateringar.
     */
    public function index(): Response
    {
        $rawPage = $this->request->get['page'] ?? 1;
        $page = is_numeric($rawPage) ? (int) $rawPage : 1;

        // Vi använder paginate för att hålla listan lätthanterlig
        $updates = SystemUpdate::query()
            ->orderBy('released_at', 'DESC')
            ->paginate(10, $page);

        return $this->view('admin.system-update.index', [
            'updates' => $updates,
        ]);
    }

    /**
     * Visa formulär för att skapa en ny uppdatering.
     */
    public function create(): Response
    {
        return $this->view('admin.system-update.create');
    }

    /**
     * Lagra en ny systemuppdatering i databasen.
     */
    public function store(): Response
    {
        $this->before();

        $data = $this->request->post;

        // Validera indata
        $validator = new Validator($data, [
            'version'     => 'required|min:1|max:20',
            'title'       => 'required|min:3|max:100',
            'description' => 'required|min:10',
            'released_at' => 'required',
        ]);

        if (!$validator->validate()) {
            $this->request->session()->set('old', $data);

            return $this->view('admin.system-update.create', [
                'errors' => $validator->errors(),
            ]);
        }

        $this->request->session()->remove('old');

        // Hämta värden säkert för PHPStan
        $version = is_string($data['version'] ?? null) ? $data['version'] : '';
        $title = is_string($data['title'] ?? null) ? $data['title'] : '';
        $description = is_string($data['description'] ?? null) ? $data['description'] : '';
        $releasedAtRaw = is_string($data['released_at'] ?? null) ? $data['released_at'] : date('Y-m-d');

        // Hantera tidstämpel för datum (tillägg av tid)
        $releasedAt = $releasedAtRaw;
        if (strlen($releasedAt) === 10) {
            $releasedAt .= ' ' . date('H:i:s');
        }

        // Tvinga is_major till heltal (1 om den finns och är '1', annars 0)
        $isMajor = (isset($data['is_major']) && $data['is_major'] === '1') ? 1 : 0;

        // Skapa och spara modellen
        $update = new SystemUpdate();

        $update->fill([
            'version'     => $version,
            'title'       => $title,
            'description' => $description,
            'released_at' => $releasedAt,
            'is_major'    => $isMajor,
        ]);

        $update->save();

        $this->request->session()->setFlashMessage(
            "Systemuppdatering {$update->version} har publicerats framgångsrikt."
        );

        return new RedirectResponse(route('admin.system-update.index'));
    }

    public function edit(string $id): Response
    {
        $update = SystemUpdate::find($id);
        if (!$update) {
            return new RedirectResponse(route('admin.system-update.index'));
        }
        return $this->view('admin.system-update.edit', ['update' => $update]);
    }

    /**
     * Uppdatera en befintlig systemuppdatering.
     */
    public function update(string $id): Response
    {
        $this->before();

        $update = SystemUpdate::find($id);

        if (!$update) {
            $this->request->session()->setFlashMessage("Uppdateringen kunde inte hittas.", "error");
            return new RedirectResponse(route('admin.system-update.index'));
        }

        $data = $this->request->post;

        // Validera data
        $validator = new Validator($data, [
            'version'     => 'required|min:1|max:20',
            'title'       => 'required|min:3|max:100',
            'description' => 'required|min:10',
            'released_at' => 'required',
        ]);

        if (!$validator->validate()) {
            $this->request->session()->set('old', $data);

            return $this->view('admin.system-update.edit', [
                'update' => $update,
                'errors' => $validator->errors(),
            ]);
        }

        $this->request->session()->remove('old');

        // Hämta värden säkert för PHPStan
        $version = is_string($data['version'] ?? null) ? $data['version'] : '';
        $title = is_string($data['title'] ?? null) ? $data['title'] : '';
        $description = is_string($data['description'] ?? null) ? $data['description'] : '';
        $releasedAtRaw = is_string($data['released_at'] ?? null) ? $data['released_at'] : date('Y-m-d');

        // Hantera tidstämpel för datum
        $releasedAt = $releasedAtRaw;
        if (strlen($releasedAt) === 10) {
            $releasedAt .= ' ' . date('H:i:s');
        }

        // Tvinga is_major till heltal (1 om den finns och är '1', annars 0)
        $isMajor = (isset($data['is_major']) && $data['is_major'] === '1') ? 1 : 0;

        // Uppdatera modellen
        $update->fill([
            'version'     => $version,
            'title'       => $title,
            'description' => $description,
            'released_at' => $releasedAt,
            'is_major'    => $isMajor,
        ]);

        $update->save();

        $this->request->session()->setFlashMessage(
            "Systemuppdatering {$update->version} har sparats."
        );

        return new RedirectResponse(route('admin.system-update.index'));
    }

    /**
     * Radera en uppdatering (Valfritt, om du vill ha funktionen).
     */
    public function delete(string $id): Response
    {
        $this->before();

        $update = SystemUpdate::find($id);

        if ($update) {
            $update->forceDelete();
            $this->request->session()->setFlashMessage("Uppdateringen har raderats.");
        }

        return new RedirectResponse(route('admin.system-update.index'));
    }
}
