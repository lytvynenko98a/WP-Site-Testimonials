=== Site Testimonials ===
Contributors: Yurii Lytvynenko  
Tags: testimonials, custom-post-type, shortcode, form  
Stable tag: 1.0.0  
License: GPL-2.0-or-later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  

== Description ==  
**Site Testimonials** — мінімалістичний плагін, що дозволяє відвідувачам залишати відгуки через фронт-форму.  
Кожен відгук зберігається як *Custom Post Type* (**Testimonials**) і керується в адмін-панелі.  
Шорткоди:  

* `[site_testimonials_form]` — форма надсилання відгуку (ім’я, email, повідомлення).  
* `[site_testimonials]` — вивід опублікованих відгуків (параметри `count`, `order`).  

== Installation ==  

1. Завантажте папку **`site-testimonials`** до `wp-content/plugins/`.  
2. Увімкніть плагін через меню **Плагіни → Installed Plugins**.  
3. Додайте шорткод `[site_testimonials_form]` на будь-яку сторінку для показу форми.  
4. (Необов’язково) додайте шорткод `[site_testimonials]` для показу опублікованих відгуків.

== Usage ==  

* **Форма:**  
  `[site_testimonials_form]`  

* **Вивід відгуків:**  
  `[site_testimonials count="10" order="ASC"]`  

| Атрибут | Тип | За замовч. | Опис |
|---------|-----|-----------|------|
| `count` | int | `5`       | Скільки відгуків показати |
| `order` | enum (`ASC\|DESC`) | `DESC` | Напрямок сортування |

== Frequently Asked Questions ==  

= Чи можу я автоматично публікувати відгуки без модерації? =  
Так. У файлі плагіна в методі `handle_form_submit()` замініть `post_status` із `'pending'` на `'publish'`.

= Як змінити стилі форми? =  
Створіть файл `assets/site-testimonials.css` у папці плагіна й підключіть потрібні правила — плагін уже реєструє цей CSS.

== Screenshots ==  

1. Front-end форма відгуку  
2. Список «Testimonials» в адмін-панелі з колонкою Email  
3. Приклад виводу опублікованих відгуків на сторінці

== Changelog ==  

= 1.0.0 =  
* Перша стабільна версія: CPT, форма, шорткоди, базові стилі, адмін-колонки.

== Upgrade Notice ==  

= 1.0.0 =  
Початковий реліз. Просто замініть файл *site-testimonials.php*, якщо оновлюєте з бета-версії.
