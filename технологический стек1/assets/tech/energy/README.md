# Энергетические установки

Каталог содержит художественные и игровые слои стази-сборщиков.

## Воранеры V01

- `source/voraner-stasis-collector-v01-concept.png` — реалистичный концепт здания в среде кольцевого мира;
- `source/voraner-stasis-collector-v01-cutout-raw.png` — необрезанный прозрачный мастер после отделения от среды;
- `exports/voraner-stasis-collector-v01-color.png` — прозрачный цветной слой, полученный из того же концепта без пустыни и дальней падающей тени;
- `metadata/voraner-stasis-collector-v01.json` — одноклеточный игровой манифест.
- `previews/voraner-stasis-collector-v01-single-cell.png` — контрольная посадка на одиночную клетку при масштабе сцены 190%.
- `previews/voraner-stasis-collector-v01-residential-district.png` — сборщик рядом с жилыми домами на 19-клеточном полигоне.
- `previews/voraner-stasis-collector-v01-district-wide.png` — проверка читаемости на 61-клеточном окружении.

Сборщик подключён к прототипу пакетом `voranerEnergy01`. Падающая тень вычисляется клиентом по альфа-силуэту. Отдельный слой свечения пока не изготовлен: на первом просмотре проверяются масштаб, якорь, перекрытие и сохранение реалистичного художественного стиля.

Смешанный пакет `voranerDistrictEnergy` оставляет жилые варианты и объединения без изменений, а сборщик закрепляет на клетке 8 как отдельную группу `stasis-energy`. Поэтому энергетическая установка не участвует в жилом объединении 3 → 1 или 7 → 1.

Контрольный адрес:

```text
http://127.0.0.1:4174/prototype/?v=20261014-4&package=voranerEnergy01&scenario=single&zoom=190&selectedCell=0&shadowMode=calibrated&courtyard=0
```

Жилой квартал со сборщиком:

```text
http://127.0.0.1:4174/prototype/?v=20261018-1&package=voranerDistrictEnergy&scenario=district&zoom=170&selectedCell=8&shadowMode=calibrated
```

Уменьшенный вид окружения:

```text
http://127.0.0.1:4174/prototype/?v=20261018-1&package=voranerDistrictEnergy&scenario=districtWide&zoom=120&selectedCell=8&shadowMode=calibrated
```
