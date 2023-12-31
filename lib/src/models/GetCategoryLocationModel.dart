// To parse this JSON data, do
//
//     final getCategoryLocationModel = getCategoryLocationModelFromJson(jsonString);

// ignore_for_file: non_constant_identifier_names, file_names

import 'dart:convert';

GetCategoryLocationModel getCategoryLocationModelFromJson(String str) =>
    GetCategoryLocationModel.fromJson(json.decode(str));

String getCategoryLocationModelToJson(GetCategoryLocationModel data) =>
    json.encode(data.toJson());

class GetCategoryLocationModel {
  bool success;
  String message;
  Data data;

  GetCategoryLocationModel({
    required this.success,
    required this.message,
    required this.data,
  });

  factory GetCategoryLocationModel.fromJson(Map<String, dynamic> json) =>
      GetCategoryLocationModel(
        success: json["success"],
        message: json["message"],
        data: Data.fromJson(json["data"]),
      );

  Map<String, dynamic> toJson() => {
        "success": success,
        "message": message,
        "data": data.toJson(),
      };
}

class Data {
  List<Category> categories;

  Data({
    required this.categories,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        categories: List<Category>.from(
            json["categories"].map((x) => Category.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "categories": List<dynamic>.from(categories.map((x) => x.toJson())),
      };
}

class Category {
  int id;
  String icon;
  int parentId;
  String slug;
  String banner;
  String title;
  String image;
  List<Category>? subCategories;
  List<dynamic>? childCategories;
  String? latlong;
  String? categoryFilter;
  String? number;
  String? soc_fb;
  String? soc_yt;
  String? soc_in;

  Category({
    required this.id,
    required this.icon,
    required this.parentId,
    required this.slug,
    required this.banner,
    required this.title,
    required this.image,
    this.subCategories,
    this.childCategories,
    this.latlong,
    this.categoryFilter,
    this.number,
    this.soc_fb,
    this.soc_yt,
    this.soc_in,
  });

  factory Category.fromJson(Map<String, dynamic> json) => Category(
        id: json["id"],
        icon: json["icon"],
        parentId: json["parent_id"],
        slug: json["slug"],
        banner: json["banner"],
        title: json["title"],
        image: json["image"],
        categoryFilter: json['category_filter'],
        number: json['number'],
        soc_fb: json['soc_fb'],
        soc_yt: json['soc_yt'],
        soc_in: json['soc_in'],
        subCategories: json["sub_categories"] == null
            ? []
            : List<Category>.from(
                json["sub_categories"]!.map((x) => Category.fromJson(x))),
        childCategories: json["child_categories"] == null
            ? []
            : List<dynamic>.from(json["child_categories"]!.map((x) => x)),
        latlong: json["latlong"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "icon": icon,
        "parent_id": parentId,
        "slug": slug,
        "banner": banner,
        "title": title,
        "image": image,
        "sub_categories": subCategories == null
            ? []
            : List<dynamic>.from(subCategories!.map((x) => x.toJson())),
        "child_categories": childCategories == null
            ? []
            : List<dynamic>.from(childCategories!.map((x) => x)),
        "latlong": latlong,
        'category_filter': categoryFilter,
        'number': number,
        'soc_fb': soc_fb,
        'soc_yt': soc_yt,
        'soc_in': soc_in,
      };
}
