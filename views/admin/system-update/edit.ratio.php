{% extends "layouts/admin.ratio.php" %}
{% block title %}Redigera uppdatering{% endblock %}
{% block pageId %}admin-update-edit{% endblock %}
{% block body %}
    <section>
      <div class="mb-8">
        <h1 class="text-3xl font-semibold mb-2">Redigera uppdatering</h1>
        <p class="text-gray-600">Uppdatera informationen för version {{ $update->version }}.</p>
      </div>

      <form action="{{ route('admin.system-update.update', ['id' => $update->id]) }}" method="post" class="w-full max-w-2xl border border-gray-200 p-6 rounded-xl bg-white shadow-sm">
        {{ csrf_field()|raw }}

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Version -->
            <div class="relative">
              <label for="version" class="block text-sm font-medium text-slate-700 mb-1.5 ml-1">Versionsnummer</label>
              <input type="text"
                     name="version"
                     id="version"
                     value="{{ old('version') ?: $update->getAttribute('version') }}"
                     placeholder="T.ex. v1.0.1"
                     class="w-full text-sm border-slate-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm">
              {% if (error($errors, 'version')) : %}
                <span class="block absolute -bottom-5 left-1 text-xxs text-red-600">{{ error($errors, 'version') }}</span>
              {% endif %}
            </div>

            <!-- Releasedatum -->
            <div class="relative">
              <label for="released_at" class="block text-sm font-medium text-slate-700 mb-1.5 ml-1">Publiceringsdatum</label>
              <input type="date"
                     name="released_at"
                     id="released_at"
                     value="{{ old('released_at') ?: date('Y-m-d', strtotime($update->getAttribute('released_at'))) }}"
                     class="w-full text-sm border-slate-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm">
              {% if (error($errors, 'released_at')) : %}
                <span class="block absolute -bottom-5 left-1 text-xxs text-red-600">{{ error($errors, 'released_at') }}</span>
              {% endif %}
            </div>
        </div>

        <!-- Rubrik -->
        <div class="relative mb-8">
          <label for="title" class="block text-sm font-medium text-slate-700 mb-1.5 ml-1">Rubrik</label>
          <input type="text"
                 name="title"
                 id="title"
                 value="{{ old('title') ?: $update->getAttribute('title') }}"
                 placeholder="T.ex. Prestandaförbättringar"
                 class="w-full text-sm border-slate-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm">
          {% if (error($errors, 'title')) : %}
            <span class="block absolute -bottom-5 left-1 text-xxs text-red-600">{{ error($errors, 'title') }}</span>
          {% endif %}
        </div>

        <!-- Beskrivning -->
        <div class="relative mb-8">
          <label for="description" class="block text-sm font-medium text-slate-700 mb-1.5 ml-1">Beskrivning</label>
          <textarea name="description"
                    id="description"
                    rows="5"
                    placeholder="Beskriv vad som är nytt..."
                    class="w-full text-sm border-slate-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm">{{ old('description') ?: $update->getAttribute('description') }}</textarea>
          {% if (error($errors, 'description')) : %}
            <span class="block absolute -bottom-5 left-1 text-xxs text-red-600">{{ error($errors, 'description') }}</span>
          {% endif %}
        </div>

        <!-- Major Update Toggle -->
        <div class="flex items-center gap-3 mb-8 ml-1">
            <?php
                // Kolla om vi har '1' i old (från ett tidigare misslyckat store/update)
                // Eller om databas-värdet är 1 (om ingen old-data finns för just denna nyckel)
                $isMajorOld = old('is_major');
                $isMajorDb = $update->getAttribute('is_major');

                // Om old('is_major') är '1', eller om old är tom men DB är 1
                $checked = ($isMajorOld === '1' || ($isMajorOld === '' && $isMajorDb == 1));
            ?>
            <input type="checkbox" name="is_major" id="is_major" value="1" {{ $checked ? 'checked' : '' }}
                   class="size-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500">
            <label for="is_major" class="text-sm font-medium text-slate-700">Viktig uppdatering (Major)</label>
        </div>

        <!-- Knappar -->
        <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
          <button type="submit" class="bg-indigo-600 text-white py-2.5 px-6 rounded-lg font-semibold hover:bg-indigo-700 transition duration-300 shadow-md shadow-indigo-100">
            Spara ändringar
          </button>
          <a href="{{ route('admin.system-update.index') }}" class="py-2.5 px-6 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition duration-300">
            Avbryt
          </a>
        </div>
      </form>
    </section>
{% endblock %}