# This files contains your custom actions which can be used to run
# custom Python code.
#
# See this guide on how to implement these action:
# https://rasa.com/docs/rasa/custom-actions

from typing import Any, Text, Dict, List
from rasa_sdk import Action, Tracker
from rasa_sdk.executor import CollectingDispatcher
import requests
import json

class ActionRekomendasiLaptop(Action):

    def name(self) -> Text:
        return "action_rekomendasi_laptop"

    def run(self, dispatcher: CollectingDispatcher,
            tracker: Tracker,
            domain: Dict[Text, Any]) -> List[Dict[Text, Any]]:

        # Extract entities from user message
        merek = next(tracker.get_latest_entity_values("merek"), None)
        harga_max = next(tracker.get_latest_entity_values("harga"), None)
        
        # Get the latest user message
        user_message = tracker.latest_message.get('text', '').lower()
        
        try:
            # Call Laravel API to search laptops
            api_url = "http://localhost:8000/api/chatbot/search-laptops"
            params = {}
            
            if merek:
                params['merek'] = merek
            if harga_max:
                params['harga_max'] = harga_max
                
            # If no specific entities found, try to extract from message
            if not merek and not harga_max:
                # Extract brand from message - enhanced brand detection
                brands = ['asus', 'acer', 'hp', 'lenovo', 'dell', 'apple', 'msi', 'alienware', 'samsung', 'xiaomi', 'macbook', 'rog', 'thinkpad', 'ideapad', 'legion', 'inspiron', 'xps', 'predator', 'omen']
                for brand in brands:
                    if brand in user_message:
                        params['merek'] = brand.upper()
                        break
                
                # Extract price from message - improved price detection
                if 'juta' in user_message:
                    words = user_message.split()
                    for i, word in enumerate(words):
                        if word.replace('.', '').replace(',', '').isdigit():
                            try:
                                price = float(word.replace('.', '').replace(',', '')) * 1000000
                                params['harga_max'] = int(price)
                                break
                            except:
                                continue
                        # Handle cases like "15juta" or "20jutaan"
                        elif 'juta' in word:
                            clean_word = word.replace('juta', '').replace('jutaan', '').replace('.', '').replace(',', '')
                            if clean_word.isdigit():
                                try:
                                    price = float(clean_word) * 1000000
                                    params['harga_max'] = int(price)
                                    break
                                except:
                                    continue
            
            response = requests.get(api_url, params=params, timeout=10)
            
            if response.status_code == 200:
                data = response.json()
                laptops = data.get('laptops', [])
                search_message = data.get('message', '')
                
                if laptops:
                    # Create structured header
                    message = f"<div class='search-results'>"
                    
                    # Use API search summary if available
                    if search_message:
                        message += f"<div class='result-header'>✅ {search_message}</div>"
                    else:
                        message += f"<div class='result-header'>🔍 Saya menemukan {len(laptops)} laptop yang sesuai</div>"
                    
                    message += f"<div class='results-list'>"
                    
                    for i, laptop in enumerate(laptops[:5], 1):  # Show max 5 results
                        nama = laptop.get('nama', 'N/A')
                        harga_formatted = laptop.get('harga_formatted', f"Rp {laptop.get('harga', 0):,.0f}".replace(',', '.'))
                        merek_laptop = laptop.get('merek', 'N/A')
                        kategori = laptop.get('kategori', 'Laptop')
                        spesifikasi = laptop.get('spesifikasi', 'Tidak ada spesifikasi')
                        detail_url = laptop.get('detail_url', '')
                        
                        # Clean and truncate specification
                        if spesifikasi and len(spesifikasi) > 80:
                            spesifikasi = spesifikasi[:80] + "..."
                        
                        message += f"<div class='laptop-item'>"
                        message += f"<div class='laptop-title'>{i}. <strong>{nama}</strong></div>"
                        message += f"<div class='laptop-details'>"
                        message += f"<div class='detail-row'>💰Harga: <span class='price'>{harga_formatted}</span></div>"
                        message += f"<div class='detail-row'>🏷️ Merek: <span class='brand'>{merek_laptop}</span></div>"
                        message += f"<div class='detail-row'>📁 Kategori: <span class='category'>{kategori}</span></div>"
                        message += f"<div class='detail-row'>💻 Spefisikasi: <span class='spec'>{spesifikasi}</span></div>"
                        
                        if detail_url:
                            message += f"<div class='detail-row'>🔗 <a href=\"{detail_url}\" target=\"_blank\" class='product-link' style='color: blue;'>Lihat Detail Produk</a></div>"
                        
                        message += f"</div></div>"
                    
                    message += f"</div>"  # Close results-list
                    
                    if len(laptops) > 5:
                        message += f"<div class='more-results'>📋 Dan {len(laptops) - 5} laptop lainnya tersedia</div>"
                    
                    message += f"<div class='footer-message'>💬 Klik link detail untuk melihat spesifikasi lengkap atau tanyakan tentang laptop tertentu!</div>"
                    message += f"</div>"  # Close search-results
                else:
                    # Create structured no-results message
                    message = f"<div class='no-results'>"
                    
                    # Check if there's a specific message from the API
                    if search_message:
                        message += f"<div class='error-header'>😔 {search_message}</div>"
                    else:
                        message += f"<div class='error-header'>😔 Maaf, tidak ada laptop yang sesuai dengan kriteria Anda</div>"
                    
                    message += f"<div class='suggestions'>"
                    message += f"<div class='suggestion-title'>🔍 Coba gunakan kriteria pencarian yang berbeda seperti:</div>"
                    message += f"<div class='suggestion-list'>"
                    message += f"<div class='suggestion-item'>• Laptop ASUS atau ROG</div>"
                    message += f"<div class='suggestion-item'>• Laptop LENOVO atau ThinkPad</div>"
                    message += f"<div class='suggestion-item'>• Laptop dibawah 15 juta</div>"
                    message += f"<div class='suggestion-item'>• Laptop gaming atau ACER Predator</div>"
                    message += f"<div class='suggestion-item'>• MacBook atau laptop Apple</div>"
                    message += f"</div></div></div>"
            else:
                message = "<div class='error-message'>😅 Maaf, terjadi kendala saat mencari laptop. Silakan coba lagi nanti.</div>"
                
        except Exception as e:
            message = "<div class='error-message'>😅 Maaf, terjadi kendala saat mencari laptop. Silakan coba lagi nanti.</div>"
            print(f"Error in action_rekomendasi_laptop: {e}")

        # Send message with HTML support
        dispatcher.utter_message(
            text=message,
            parse_mode='HTML'  # Enable HTML parsing
        )
        return []

# class ActionDefaultFallback(Action):
#     def name(self) -> Text:
#         return "action_default_fallback"

#     def run(self, dispatcher: CollectingDispatcher,
#             tracker: Tracker,
#             domain: Dict[Text, Any]) -> List[Dict[Text, Any]]:
        
#         dispatcher.utter_message(text="Maaf, saya tidak mengerti maksud Anda. Bisakah Anda mengulangi pertanyaan dengan cara yang berbeda?")
#         return []
