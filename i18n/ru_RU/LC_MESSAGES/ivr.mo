��    @        Y         �     �  Z   �  Z   �     ?     L  Q  k  &   �     �     �       &   2     Y     s  *   �     �     �  ?   �  $   	     1	  B   =	     �	     �	     �	     �	     �	     �	     �	     �	     �	     �	     
     
     '
     ?
  L   D
  U   �
  �   �
  S   �  l        q     x     �     �     �  Q  �               /     A     Q     i     o     �     �     �     �     �     �  	          
   *     5  -   8  �  f     `  �   u  �        �  <   �  m    /   �     �  '   �     �  /        A  (   _  [   �     �     �  �     e   �     �  �     ,   �  
   �  )   �  6   �  2   0  I   c  6   �  '   �  8     %   E  7   k  !   �  F   �       �     t   �  D  .  �   s           $     #   ;     _  b   z  �  �     n"  ,   }"  *   �"     �"  ?   �"     3#  /   F#     v#     �#  *   �#  %   �#  %   �#  &   #$     J$  '   a$     �$     �$  \   �$                                 &      @              %   .             $   '       1               +                        
           (          0   ;   9   3      7           !   6       "      8          <   )   	               4         ,   5   /          ?   =              >          :   *         2   #          -       Add IVR After playing the Invalid Retry Recording the system will replay the main IVR Announcement After playing the Timeout Retry Recording the system will replay the main IVR Announcement Announcement Append Announcement on Timeout Check this box to have this option return to a parent IVR if it was called from a parent IVR. If not, it will go to the chosen destination.<br><br>The return path will be to any IVR that was in the call path prior to this IVR which could lead to strange results if there was an IVR called in the call path but not immediately before this Checking for invalid_append_announce.. Checking for invalid_id.. Checking for invalid_ivr_ret.. Checking for retvm.. Checking for timeout_append_announce.. Checking for timeout_id.. Checking for timeout_ivr_ret.. Checking if announcements need migration.. Default Delete Delete this entry. Dont forget to click Submit to save changes! Deprecated Directory used by %s IVRs Destination Destination to send the call to after Timeout Recording is played. Enable Direct Dial Ext IVR IVR Description IVR Entries IVR General Options IVR Name IVR: %s IVR: %s / Option: %s Invalid Destination Invalid Recording Invalid Retries Invalid Retry Recording None Number of times to retry when no DTMF is heard and the IVR choice times out. Number of times to retry when receiving an invalid/unmatched response from the caller Prompt to be played before sending the caller to an alternate destination due to the caller pressing 0 or receiving the maximum amount of invalid/unmatched responses (as determined by Invalid Retries) Prompt to be played when a timeout occurs, before prompting the caller to try again Prompt to be played when an invalid/unmatched response is received, before prompting the caller to try again Return Return on Invalid Return on Timeout Return to IVR Return to IVR after VM There are %s IVRs that have the legacy Directory dialing enabled. This has been deprecated and will be removed from future releases. You should convert your IVRs to use the Directory module for this functionality and assign an IVR destination to a desired Directory. You can install the Directory module from the Online Module Repository Timeout Timeout Destination Timeout Recording Timeout Retries Timeout Retry Recording added adding announcement_id field.. already migrated digits pressed dropping announcement field.. fatal error migrate to recording ids.. migrated %s entries migrating no announcement field??? not needed ok posting notice about deprecated functionality Project-Id-Version: 1.4
Report-Msgid-Bugs-To: 
POT-Creation-Date: 2015-09-14 11:45-0700
PO-Revision-Date: 2015-05-01 13:36+0200
Last-Translator: Yuriy <alliancesko@gmail.com>
Language-Team: Russian <http://weblate.freepbx.org/projects/freepbx/ivr/ru_RU/>
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 8bit
Language: ru_RU
Plural-Forms: nplurals=3; plural=n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2;
X-Generator: Weblate 2.2-dev
 Добавить IVR После проигрывания записи Неверный Повтор система воспроизведет основное приветствие IVR После проигрывания записи Тайм-аут Повтора система воспроизведет основное приветствие IVR Приветствие Добавьте приветствие о Тайм-ауте Отметьте здесь, если нужно возвращать в родительское Меню, если вызов сюда поступает из другого Меню. Если не отмечено, вызов поступает на выбор назначений.<br><br>Возврат можно перенаправлять и в любые другие Меню, из которых также производится вызов в действующее Меню, но возврат и перенаправление может привести к неожиданным результатам Проверка на invalid_append_announce.. Проверка invalid_id.. Проверка на invalid_ivr_ret.. Проверка retvm.. Проверка на timeout_append_announce.. Проверка timeout_id.. Проверка на  timeout_ivr_ret.. Проверка, нуждаются ли объявления в перемещении... По умолчанию Удалить Удалить этот вход. Не забудьте нажать ПРИМЕНИТЬ, чтобы сохранить изменения! Устаревшие Каталоги используемые %s Интерактивным меню Назначение Назначения направления звонка после воспроизведения записи о Тайм-ауте. Разрешить прямые наборы Номер Интерактивное меню (IVR) Описание Интерактивного меню Пункты Интерактивного меню Основыне настройки Интерактивного меню Название Интерактивного меню Интерактивное меню: %s Интерактивное меню: %s / Опция: %s Неверное назначение Сообщение при неудачном вводе Неудачные попытки Воспроизведение при неуданой попытке  Нет Количество попыток повтора при отсутствии попыток набора DTMF и тайм-аута выбора пунктов IVR. Количество неудачных/несовпадающих попыток ввода от звонящего Сообщение воспроизводимое перед отправкой звонящего на альетрнативное назначение при нажатии звонящим 0 или при достижении максимального количества неудачных попыток ввода.  Подсказка, звучащая по тайм-ауту, перед просьбой звонящему попробовать еще раз Сообщение воспроизводимое когда произошла неудачная/несовпадающая попытка ввода, вопроизводится перед очередной попыткой повторить ввод.  Возврат Возврат на неверный Возврат на Тайм-аут Возврат в Меню Возвращение в Интерактивно меню посе Голосовой Почты В Интерактивном меню %s активирован набор Каталога. Эта функция устарела и будет удалена из будущих релизов. Вам следует конвертировать ваше Интерактивное меню, что бы использовать модуль Каталога для этой функциональности и присвоить назначение Интерактивного меню на выбранный каталог. Вы можете установить модуль Каталога из Онлайн Репозитория Модулей Таймаут Назначение при таймауте Сообщение при таймауте Таймаут попыток Сообщение при таймауте повторений добавлено добавление поля announcement_id.. уже перенесено нажатии цифры сброс поля объявления.. неустранимая ошибка переход к id записей.. перемещено %s записей перемещение нет поля объявления??? не нужно ok Выводит оповещение о устаревшей функциональности 