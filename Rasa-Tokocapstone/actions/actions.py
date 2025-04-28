from typing import Any, Text, Dict, List
from rasa_sdk import Action, Tracker
from rasa_sdk.executor import CollectingDispatcher
import requests

class ActionRekomendasiLaptop(Action):

    def name(self) -> Text:
        return "action_rekomendasi_laptop"

    def run(self, dispatcher: CollectingDispatcher,
            tracker: Tracker,
            domain: Dict[Text, Any]) -> List[Dict[Text, Any]]:

        brand = tracker.get_slot("brand")
        kategori = tracker.get_slot("kategori")
        min_price = tracker.get_slot("min")
        max_price = tracker.get_slot("max")

        url = "http://localhost:8000/api/products"  # Ganti dengan domain Laravel kamu kalau online
        params = {
            "brand": brand,
            "kategori": kategori,
            "min": min_price,
            "max": max_price
        }

        try:
            response = requests.get(url, params=params)
            data = response.json()

            if data:
                response_text = "Berikut beberapa rekomendasi laptop untukmu:\n"
                for product in data:
                    response_text += f"- {product['name']} (Rp {product['price']:,}) → /product/{product['slug']}\n"
            else:
                response_text = "Maaf, kami tidak menemukan laptop sesuai kriteria kamu."

        except Exception as e:
            response_text = "Terjadi kesalahan saat menghubungi sistem kami. Coba lagi nanti."

        dispatcher.utter_message(text=response_text)
        return []
