from typing import Any, Text, Dict, List
from rasa_sdk import Action, Tracker
from rasa_sdk.executor import CollectingDispatcher
from rasa_sdk.events import SlotSet
import mysql.connector
from mysql.connector import Error

class ActionRekomendasiLaptop(Action):
    def __init__(self):
        self.connection = None
        self.cursor = None

    def name(self) -> Text:
        return "action_rekomendasi_laptop"

    def connect_to_db(self) -> bool:
        try:
            if self.connection is None or not self.connection.is_connected():
                self.connection = mysql.connector.connect(
                    host="localhost",
                    user="root",
                    password="",
                    database="tokocapstone"
                )
                self.cursor = self.connection.cursor(dictionary=True)
            return True
        except Error as e:
            print(f"Error connecting to MySQL Database: {e}")
            return False

    def run(
        self,
        dispatcher: CollectingDispatcher,
        tracker: Tracker,
        domain: Dict[Text, Any]
    ) -> List[Dict[Text, Any]]:
        # Ambil slot
        brand = tracker.get_slot("brand")
        min_price = tracker.get_slot("min")
        max_price = tracker.get_slot("max")
        events: List[SlotSet] = []

        # Jika user hanya menyebut brand, reset slot harga
        if brand and min_price is None and max_price is None:
            events.append(SlotSet("min", None))
            events.append(SlotSet("max", None))

        # Koneksi DB
        if not self.connect_to_db():
            dispatcher.utter_message(text="Maaf, terjadi kesalahan sistem. Silakan coba lagi nanti.")
            return events

        try:
            # Query dengan slug untuk membuat URL
            query = (
                "SELECT p.id, p.title, p.price, p.summary, p.stock, p.slug, "
                "c.title AS category_name "
                "FROM products p "
                "JOIN categories c ON p.cat_id = c.id "
                "WHERE p.status = 'active'"
            )
            params: List[Any] = []

            # Filter brand
            if brand:
                query += " AND LOWER(c.title) = LOWER(%s)"
                params.append(brand)

            # Filter harga
            if min_price is not None and max_price is not None:
                query += " AND p.price BETWEEN %s AND %s"
                params.extend([float(min_price) * 1_000_000, float(max_price) * 1_000_000])
            elif min_price is not None:
                query += " AND p.price >= %s"
                params.append(float(min_price) * 1_000_000)
            elif max_price is not None:
                query += " AND p.price <= %s"
                params.append(float(max_price) * 1_000_000)

            # Urutkan harga naik
            query += " ORDER BY p.price ASC"
            self.cursor.execute(query, params)
            products = self.cursor.fetchall()
            total = len(products)

            if products:
                # Batasi tampilan top 5
                if total > 5:
                    products = products[:5]
                    limit_note = "📋 Menampilkan 5 rekomendasi teratas berdasarkan harga termurah.\n\n"
                else:
                    limit_note = ""

                header = (f"🔍 Berikut daftar laptop merek {brand.upper()}:" if brand
                          else "🔍 Berikut daftar laptop yang tersedia:")
                response_text = limit_note + header + "\n\n"

                for idx, prod in enumerate(products, 1):
                    # Format harga dengan pemisah ribuan
                    formatted_price = "{:,.0f}".format(prod['price'])
                    
                    response_text += f"•────────── {idx} ──────────•\n"
                    response_text += f"📱 *{prod['title']}*\n"
                    response_text += f"💰 *Rp {formatted_price}*\n"
                    if prod.get('summary'):
                        response_text += f"ℹ️ {prod['summary']}\n"
                    
                    # Status stok dengan emoji yang lebih jelas
                    if prod['stock'] > 0:
                        response_text += f"✅ Stok: {prod['stock']} unit\n"
                    else:
                        response_text += "❌ Stok Habis\n"
                    
                    # Link dengan format yang lebih jelas
                    product_url = f"http://localhost:8000/product-detail/{prod['slug']}"
                    response_text += f'<a href="{product_url}" style="color: #36d100; text-decoration: underline;">Lihat Detail Produk</a>\n'
                    response_text += "•───────────────────────•\n\n"
            else:
                # Tidak ada produk: format pesan error yang lebih baik
                if brand:
                    self.cursor.execute(
                        "SELECT id FROM categories WHERE LOWER(title)=LOWER(%s)",
                        (brand,)
                    )
                    exists = self.cursor.fetchone()
                    if exists:
                        if min_price is not None or max_price is not None:
                            response_text = (
                                f"❌ Mohon maaf, saat ini tidak ada laptop merek {brand.upper()} "
                                "dengan kisaran harga tersebut.\n"
                                "💡 Silakan coba dengan rentang harga yang berbeda."
                            )
                        else:
                            response_text = (
                                f"❌ Mohon maaf, saat ini tidak ada laptop merek {brand.upper()} yang tersedia.\n"
                                "💡 Silakan coba merek laptop lainnya."
                            )
                    else:
                        response_text = (
                            f"❌ Mohon maaf, merek laptop {brand.upper()} tidak tersedia dalam katalog kami.\n"
                            "💡 Silakan pilih merek laptop yang tersedia di katalog kami."
                        )
                else:
                    if min_price is not None or max_price is not None:
                        response_text = (
                            "❌ Mohon maaf, tidak ada laptop yang sesuai dengan kisaran harga tersebut.\n"
                            "💡 Silakan coba dengan rentang harga yang berbeda."
                        )
                    else:
                        response_text = (
                            "❌ Mohon maaf, tidak ada laptop yang sesuai dengan kriteria Anda.\n"
                            "💡 Silakan coba dengan kriteria yang berbeda."
                        )

            dispatcher.utter_message(text=response_text)

        except Error as e:
            print(f"Error saat query: {e}")
            dispatcher.utter_message(text="Terjadi kesalahan saat mencari data. Silakan coba lagi nanti.")
        finally:
            # Tutup koneksi
            if self.cursor:
                self.cursor.close()
            if self.connection and self.connection.is_connected():
                self.connection.close()
                self.connection = None

        # Reset slot harga agar tidak mempengaruhi turn berikutnya
        events.append(SlotSet("min", None))
        events.append(SlotSet("max", None))
        # Jika perlu reset brand juga, uncomment:
        # events.append(SlotSet("brand", None))

        return events
