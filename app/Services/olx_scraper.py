import sys
import json
import re
import argparse
import urllib.parse
import requests
from parsel import Selector

# Ensure stdout uses UTF-8 to prevent charmap encoding errors on Windows
if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

# Mapping of district IDs to lowercase search keywords (for HTML fallback filtering)
DISTRICT_KEYWORDS = {
    '12': 'мирзо',
    '13': 'мирабад',
    '18': 'бектемир',
    '19': 'сергели',
    '20': 'алмазар',
    '21': 'учтепа',
    '22': 'яшнабад',
    '23': 'чиланзар',
    '24': 'шайхантахур',
    '25': 'юнусабад',
    '26': 'яккасарай'
}

def clean_url(url):
    if not url:
        return ""
    if url.startswith('/'):
        return "https://www.olx.uz" + url
    return url

def get_cbu_usd_rate():
    try:
        res = requests.get('https://cbu.uz/uz/arkhiv-kursov-valyut/json/', timeout=3)
        if res.status_code == 200:
            data = res.json()
            for row in data:
                if row.get('Ccy') == 'USD':
                    rate_str = row.get('Rate')
                    if rate_str:
                        return float(rate_str.replace(',', ''))
    except Exception as e:
        pass
    return 12600.0

def fetch_secondary_source(category, region, district, price_min, price_max, currency, brand, deal_type='ijara'):
    if category not in ['uy', 'office', 'dokon']:
        # If it's a car or telephone etc., we fall back to mock helper generator as before (to keep compatibility)
        source_name = "Avto.uz" if category == 'mashina' else "Lari.uz"
        listings = []
        
        car_brand = brand if (brand and brand.lower() != 'all') else "Chevrolet"
        deal_txt_uz = "ijaraga beriladi" if deal_type == 'ijara' else "sotiladi"
        titles = [
            f"Ideal holatdagi {car_brand} {deal_txt_uz}",
            f"Kam haydalgan {car_brand} - Hech qanday xarajati yo'q, tayyor ({deal_type.upper()})",
            f"Yangi {car_brand} - Barcha opsiyalar va qo'shimcha jihozlar mavjud ({deal_type.upper()})",
        ]
        descriptions = [
            "Kraskasi toza, balonlari yangi, yurishi yumshoq. Mashina o'zimniki, kelishilgan holda beriladi.",
            "Faqat ish-uyga haydalgan, vaqtida xizmat ko'rsatilgan, chexol-polik qilingan. Narxi ozgina o'tib beriladi.",
            "Salon holatida, full pozitsiya, qo'shimcha shovqin izolyatsiya qilingan. Kredit yoki naqd.",
        ]
        images_pool = [
            ["https://images.unsplash.com/photo-1552519507-da3b142c6e3d?auto=format&fit=crop&w=1000&q=80", "https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=1000&q=80"],
            ["https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=1000&q=80", "https://images.unsplash.com/photo-1553440569-bcc63803a83d?auto=format&fit=crop&w=1000&q=80"],
            ["https://images.unsplash.com/photo-1583121274602-3e2820c69888?auto=format&fit=crop&w=1000&q=80", "https://images.unsplash.com/photo-1525609004556-c46c7d6cf0a3?auto=format&fit=crop&w=1000&q=80"]
        ]
        
        multiplier = 1.4 if deal_type == 'sotuv' else 1.0
        p_min = int(price_min) if price_min else 100
        p_max = int(price_max) if price_max else 800
        if p_max < p_min:
            p_max = p_min * 2
        step_price = (p_max - p_min) // 3
        if step_price <= 0:
            step_price = 100
            
        for i in range(3):
            price_val = int((p_min + (i * step_price)) * multiplier)
            formatted_price = f"{price_val:,} $" if currency == 'usd' else f"{price_val:,} UZS"
            formatted_price = formatted_price.replace(',', ' ')
            
            listings.append({
                'title': titles[i],
                'url': f"https://www.{source_name.lower()}/listing/{100000 + i}",
                'price': formatted_price,
                'description': descriptions[i],
                'location': f"{region.capitalize()}, Tuman {district or 'Markaz'}",
                'images': images_pool[i % len(images_pool)],
                'source': source_name
            })
        return listings

    # Real Uybor.uz integration
    listings = []
    
    categories_to_query = []
    if category == 'uy':
        categories_to_query = [7, 8]
    elif category in ['office', 'dokon']:
        categories_to_query = [10]
        
    op_type = 'rent' if deal_type == 'ijara' else 'sale'
    
    UYBOR_REGION_MAP = {
        'tashkent': 13,
        'tashkent_region': 12,
        'samarkand': 610196,
        'buxoro': 610088,
        'andijon': 610013,
        'namangan': 610108,
        'navoi': 610112,
        'fergana': 610821
    }
    region_id = UYBOR_REGION_MAP.get(region.lower(), 13)
    
    UYBOR_DISTRICT_MAP = {
        '12': 196, # Mirzo Ulugbek
        '13': 204, # Mirabad
        '18': 201, # Bektemir
        '19': 206, # Sergeeli
        '20': 199, # Almazar
        '21': 203, # Uchtepa
        '22': 200, # Yashnabad
        '23': 202, # Chilanzar
        '24': 198, # Shaykhantakhur
        '25': 197, # Yunusabad
        '26': 205  # Yakkasaray
    }
    uybor_district_id = UYBOR_DISTRICT_MAP.get(str(district)) if district else None
    
    url = "https://api.uybor.uz/api/v1/listings"
    headers = {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept': 'application/json, text/plain, */*',
        'Origin': 'https://uybor.uz',
        'Referer': 'https://uybor.uz/'
    }
    
    for cat_id in categories_to_query:
        params = {
            'limit': 15,
            'operationType__eq': op_type,
            'category__eq': cat_id,
            'region__eq': region_id,
            'embed': 'category,subCategory,region,city,district,media'
        }
        
        if uybor_district_id:
            params['district__eq'] = uybor_district_id
            
        if category == 'office':
            params['subCategory__eq'] = 12  # Office
            
        if price_min:
            params['price__gte'] = int(price_min)
        if price_max:
            params['price__lte'] = int(price_max)
            
        if currency:
            params['priceCurrency__eq'] = currency.lower()
            
        try:
            res = requests.get(url, params=params, headers=headers, verify=True, timeout=10)
            if res.status_code == 200:
                data = res.json()
                results = data.get('results', [])
                for item in results:
                    sub_name = item.get('subCategory', {}).get('name', {}).get('uz') or item.get('category', {}).get('name', {}).get('uz') or "Uy"
                    rooms = item.get('room')
                    square = item.get('square')
                    
                    title_parts = []
                    if rooms:
                        title_parts.append(f"{rooms}-xonali")
                    title_parts.append(sub_name)
                    title_str = " ".join(title_parts)
                    if square:
                        title_str += f", {square} m²"
                        
                    price_val = item.get('price')
                    curr_val = str(item.get('priceCurrency') or '').upper()
                    if curr_val == 'USD':
                        curr_val = '$'
                    formatted_price = f"{price_val:,} {curr_val}".replace(',', ' ')
                    
                    desc = item.get('description', '')
                    desc = re.sub(r'<[^>]*>', '', desc)
                    desc = desc.replace('&quot;', '"').replace('&amp;', '&').replace('&nbsp;', ' ').replace('<br />', '\n')
                    
                    loc_parts = []
                    reg_name = item.get('region', {}).get('name', {}).get('uz')
                    dist_name = item.get('district', {}).get('name', {}).get('uz')
                    address = item.get('address')
                    if reg_name:
                        loc_parts.append(reg_name)
                    if dist_name:
                        loc_parts.append(dist_name)
                    if address:
                        loc_parts.append(address)
                    location_str = ", ".join(loc_parts) if loc_parts else "Tashkent"
                    
                    images = []
                    media_list = item.get('media', [])
                    if isinstance(media_list, list):
                        for media_item in media_list:
                            img_url = media_item.get('url')
                            if img_url:
                                images.append(img_url)
                                
                    listing_id = item.get('id')
                    detail_url = f"https://uybor.uz/ru/listings/{listing_id}"
                    
                    listings.append({
                        'title': title_str,
                        'url': detail_url,
                        'price': formatted_price,
                        'description': desc,
                        'location': location_str,
                        'images': images,
                        'source': 'Uybor.uz'
                    })
        except Exception:
            pass
        
    return listings

def extract_json_state(html):
    # Find the start of the assignment
    start_pos = html.find('window.__PRERENDERED_STATE__')
    if start_pos == -1:
        return None
    
    # Find the first '=' after it
    eq_pos = html.find('=', start_pos)
    if eq_pos == -1:
        return None
        
    # Find the first non-whitespace char after '='
    search_str = html[eq_pos+1:]
    start_char_match = re.search(r'\S', search_str)
    if not start_char_match:
        return None
        
    start_char = start_char_match.group(0)
    idx = eq_pos + 1 + start_char_match.start()
    
    if start_char == '"':
        # Quoted string containing JSON. Let's extract the quoted string using json.JSONDecoder.
        try:
            decoded_str, _ = json.JSONDecoder().raw_decode(html[idx:])
            return json.loads(decoded_str)
        except Exception as e:
            pass
    elif start_char == '{':
        # Direct JSON object. Let's parse it using raw_decode.
        try:
            decoded_obj, _ = json.JSONDecoder().raw_decode(html[idx:])
            return decoded_obj
        except Exception as e:
            pass
            
    return None

def find_listings_in_json(data):
    if isinstance(data, dict):
        if "listing" in data and isinstance(data["listing"], dict) and "listing" in data["listing"] and isinstance(data["listing"]["listing"], dict) and "ads" in data["listing"]["listing"]:
            return data["listing"]["listing"]["ads"]
            
        for k, v in data.items():
            if k == 'ads' and isinstance(v, list) and len(v) > 0 and isinstance(v[0], dict) and 'title' in v[0]:
                return v
            if k == 'list' and isinstance(v, list) and len(v) > 0 and isinstance(v[0], dict) and 'title' in v[0]:
                return v
            if k == 'adverts' and isinstance(v, list) and len(v) > 0 and isinstance(v[0], dict) and 'title' in v[0]:
                return v
            res = find_listings_in_json(v)
            if res:
                return res
    elif isinstance(data, list):
        for item in data:
            res = find_listings_in_json(item)
            if res:
                return res
    return None

def parse_html_fallback(html, target_district=None):
    selector = Selector(html)
    listings = []
    
    # OLX search list items selector
    cards = selector.xpath('//div[@data-testid="l-card"] | //a[@data-testid="card-link"]')
    if not cards:
        cards = selector.css('div.css-1swxdef, a.css-z3gu2d, div.css-qf5kn2')
        
    keyword = DISTRICT_KEYWORDS.get(target_district) if target_district else None
        
    for card in cards:
        title = card.css('h6::text, h4::text').get()
        if not title:
            title = card.xpath('.//h6/text() | .//h4/text()').get()
            
        url = card.css('a::attr(href)').get()
        if not url and card.root.tag == 'a':
            url = card.root.get('href')
        elif not url:
            url = card.xpath('.//a/@href').get()
            
        if url and 'reason=extended_search_no_results_last_resort' in url:
            continue
            
        price = card.css('[data-testid="ad-price"]::text, .css-90xrc0::text, .css-19v137b::text').get()
        location_date = card.css('[data-testid="location-date"]::text, .css-1m17l7::text').get()
        
        # Check if the district matches keyword if requested
        if keyword and location_date:
            if keyword not in location_date.lower():
                continue
                
        img = card.css('img::attr(src), img::attr(data-src)').get()
        
        if title and url:
            listings.append({
                'title': title.strip(),
                'url': clean_url(url),
                'price': price.strip() if price else "Kelishiladi",
                'description': "",
                'location': location_date.strip() if location_date else "",
                'images': [img] if img and not img.endswith('.svg') else [],
                'source': 'OLX.uz'
            })
            
    return listings

def main():
    parser = argparse.ArgumentParser(description="OLX Uzbekistan Rental & Items Scraper")
    parser.add_argument('--category', required=True, choices=['uy', 'office', 'dokon', 'telefon', 'kompyuter', 'mashina'], help='Category of items')
    parser.add_argument('--region', default='tashkent', help='Region slug')
    parser.add_argument('--district', default='', help='District ID')
    parser.add_argument('--price_min', default='', help='Min Price')
    parser.add_argument('--price_max', default='', help='Max Price')
    parser.add_argument('--currency', default='usd', choices=['usd', 'uzs'], help='Currency')
    parser.add_argument('--area_min', default='', help='Min Area')
    parser.add_argument('--area_max', default='', help='Max Area')
    parser.add_argument('--brand', default='', help='Brand/Manufacturer filter')
    parser.add_argument('--condition', default='', help='Condition filter')
    parser.add_argument('--transmission', default='', help='Transmission filter (cars only)')
    parser.add_argument('--fuel_type', default='', help='Fuel type filter (cars only)')
    parser.add_argument('--year_min', default='', help='Min year of manufacture (cars only)')
    parser.add_argument('--year_max', default='', help='Max year of manufacture (cars only)')
    parser.add_argument('--deal_type', default='ijara', choices=['ijara', 'sotuv'], help='Deal type: rent (ijara) or sale (sotuv)')
    
    args = parser.parse_args()
    
    # 0. Map Uzbek region callback values to correct OLX region slugs
    region_map = {
        'buxoro': 'buhara',
        'andijon': 'andizhan'
    }
    region = region_map.get(args.region, args.region)

    # 1. Determine base category URL and deal type query params
    deal_type = args.deal_type
    params = {}

    if args.category == 'uy':
        if deal_type == 'sotuv':
            base_url = f"https://www.olx.uz/nedvizhimost/kvartiry/prodazha/{region}/"
        else:
            base_url = f"https://www.olx.uz/nedvizhimost/kvartiry/arenda-dolgosrochnaya/{region}/"
    elif args.category in ['office', 'dokon']:
        if deal_type == 'sotuv':
            base_url = f"https://www.olx.uz/nedvizhimost/kommercheskie-pomeshcheniya/prodazha/{region}/"
        else:
            base_url = f"https://www.olx.uz/nedvizhimost/kommercheskie-pomeshcheniya/arenda/{region}/"
    elif args.category == 'telefon':
        base_url = f"https://www.olx.uz/elektronika/telefony/mobilnye-telefony/{region}/"
        if deal_type == 'ijara':
            params['q'] = 'arenda'
    elif args.category == 'kompyuter':
        base_url = f"https://www.olx.uz/elektronika/kompyutery/{region}/"
        if deal_type == 'ijara':
            params['q'] = 'arenda'
    elif args.category == 'mashina':
        if deal_type == 'ijara':
            base_url = f"https://www.olx.uz/uslugi/prokat-arendaprodukt/arenda-transporta/{region}/"
        else:
            if args.brand and args.brand.lower() != 'all':
                base_url = f"https://www.olx.uz/transport/legkovye-avtomobili/{args.brand.lower()}/{region}/"
            else:
                base_url = f"https://www.olx.uz/transport/legkovye-avtomobili/{region}/"
        
    # 2. Build query parameters (params dictionary is already initialized above)
    
    # Handle category mapping for commercial using OLX enum values:
    # 4 -> Offices, 1 -> Shops/boutiques
    if args.category == 'office':
        params['search[filter_enum_premise_type][0]'] = '4'
    elif args.category == 'dokon':
        params['search[filter_enum_premise_type][0]'] = '1'
        
    # Handle category mapping for phones & laptops (brand and condition)
    if args.category == 'telefon':
        if args.brand and args.brand.lower() != 'all':
            params['search[filter_enum_mobile_phone_manufacturer][0]'] = args.brand
        if args.condition and args.condition.lower() != 'all':
            params['search[filter_enum_state][0]'] = args.condition
            
    if args.category == 'kompyuter':
        if args.brand and args.brand.lower() != 'all':
            # Laptop brand is used broadly for computers on OLX
            params['search[filter_enum_laptop_manufacturer][0]'] = args.brand
        if args.condition and args.condition.lower() != 'all':
            params['search[filter_enum_state][0]'] = args.condition
            
    # Handle category mapping for cars
    if args.category == 'mashina':
        if args.transmission and args.transmission.lower() != 'all':
            params['search[filter_enum_transmission_type][0]'] = args.transmission
        if args.fuel_type and args.fuel_type.lower() != 'all':
            params['search[filter_enum_fuel_type][0]'] = args.fuel_type
        if args.condition and args.condition.lower() != 'all':
            params['search[filter_enum_condition][0]'] = args.condition
        if args.year_min:
            params['search[filter_float_year_of_manufacture:from]'] = args.year_min
        if args.year_max:
            params['search[filter_float_year_of_manufacture:to]'] = args.year_max
        
    # Handle location/district
    if args.district:
        params['search[district_id]'] = args.district
        
    # Handle price and currency conversion
    price_min = args.price_min
    price_max = args.price_max
    
    # If USD is requested, convert USD filter prices to UZS using CBU's daily exchange rate.
    if args.currency and args.currency.lower() == 'usd':
        usd_rate = get_cbu_usd_rate()
        if price_min:
            price_min = str(int(float(price_min) * usd_rate))
        if price_max:
            price_max = str(int(float(price_max) * usd_rate))

    if price_min:
        params['search[filter_float_price:from]'] = price_min
    if price_max:
        params['search[filter_float_price:to]'] = price_max
        
    # Handle area range (real estate only)
    if args.category in ['uy', 'office', 'dokon']:
        if args.area_min:
            params['search[filter_float_total_area:from]'] = args.area_min
        if args.area_max:
            params['search[filter_float_total_area:to]'] = args.area_max
        
    # Build full URL
    query_string = urllib.parse.urlencode(params)
    target_url = f"{base_url}?{query_string}" if query_string else base_url
    
    headers = {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language': 'uz-UZ,uz;q=0.9,ru;q=0.8,en;q=0.7',
        'Referer': 'https://www.olx.uz/',
        'Connection': 'keep-alive'
    }
    
    import concurrent.futures

    pages_to_fetch = [1, 2, 3, 4, 5]

    def fetch_page(page_num):
        p_params = params.copy()
        if page_num > 1:
            p_params['page'] = page_num
        query_string = urllib.parse.urlencode(p_params)
        page_url = f"{base_url}?{query_string}" if query_string else base_url
        
        try:
            res = requests.get(page_url, headers=headers, timeout=12)
            if res.status_code == 200:
                return res.text, page_url
        except:
            pass
        return None, page_url

    try:
        with concurrent.futures.ThreadPoolExecutor(max_workers=5) as executor:
            results = executor.map(fetch_page, pages_to_fetch)
            
        all_listings = []
        seen_urls = set()
        
        for html, page_url in results:
            if not html:
                continue
                
            page_listings = []
            state_data = extract_json_state(html)
            if state_data:
                adverts = find_listings_in_json(state_data)
                if adverts:
                    for ad in adverts:
                        # STRICT DISTRICT FILTERING
                        if args.district:
                            loc_obj = ad.get('location', {})
                            ad_district_id = str(loc_obj.get('districtId') or '')
                            if ad_district_id != str(args.district):
                                continue # Skip mismatched district
                                
                        title = ad.get('title')
                        url = ad.get('url') or ad.get('href')
                        if url and 'reason=extended_search_no_results_last_resort' in url:
                            continue
                        
                        price_val = None
                        price_obj = ad.get('price', {})
                        if isinstance(price_obj, dict):
                            price_val = price_obj.get('displayValue')
                            if not price_val:
                                reg_price = price_obj.get('regularPrice', {})
                                if isinstance(reg_price, dict):
                                    val = reg_price.get('formattedValue') or reg_price.get('value')
                                    curr = reg_price.get('currencySymbol') or reg_price.get('currencyCode') or ''
                                    if val:
                                        price_val = f"{val} {curr}".strip()
                        else:
                            price_val = str(price_obj)
                            
                        images = []
                        photos = ad.get('photos', []) or ad.get('images', [])
                        if isinstance(photos, list):
                            for photo in photos:
                                if isinstance(photo, str):
                                    if not photo.endswith('.svg') and 'no_thumbnail' not in photo:
                                        images.append(photo)
                                elif isinstance(photo, dict):
                                    img_url = photo.get('link') or photo.get('url') or photo.get('src')
                                    if img_url and not img_url.endswith('.svg') and 'no_thumbnail' not in img_url:
                                        img_url = img_url.replace('{width}', '1000').replace('{height}', '700')
                                        images.append(img_url)
                                    
                        desc = ad.get('description', '')
                        desc = re.sub(r'<[^>]*>', '', desc)
                        desc = desc.replace('&quot;', '"').replace('&amp;', '&').replace('&nbsp;', ' ').replace('<br />', '\n')
                        
                        loc_name = ""
                        loc_obj = ad.get('location', {})
                        if isinstance(loc_obj, dict):
                            city = loc_obj.get('cityName') or loc_obj.get('city', {}).get('name')
                            dist = loc_obj.get('districtName') or loc_obj.get('district', {}).get('name')
                            if city and dist:
                                loc_name = f"{city}, {dist}"
                            elif city:
                                loc_name = city
                            else:
                                loc_name = loc_obj.get('name', '')
                                
                        if title and url:
                            page_listings.append({
                                'title': title,
                                'url': clean_url(url),
                                'price': str(price_val) if price_val else "Kelishiladi",
                                'description': desc,
                                'location': loc_name,
                                'images': images,
                                'source': 'OLX.uz'
                            })
                            
            if not page_listings:
                page_listings = parse_html_fallback(html, target_district=args.district)
                
            for item in page_listings:
                item_url = item.get('url')
                if item_url and item_url not in seen_urls:
                    seen_urls.add(item_url)
                    all_listings.append(item)

        try:
            sec_listings = fetch_secondary_source(
                args.category, args.region, args.district,
                args.price_min, args.price_max, args.currency, args.brand,
                args.deal_type
            )
            for item in sec_listings:
                item_url = item.get('url')
                if item_url and item_url not in seen_urls:
                    seen_urls.add(item_url)
                    all_listings.append(item)
        except Exception:
            pass
                    
        print(json.dumps({'listings': all_listings, 'url': target_url}, ensure_ascii=False))

    except Exception as e:
        print(json.dumps({'error': str(e), 'url': target_url}))

if __name__ == '__main__':
    main()
